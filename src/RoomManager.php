<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit;

use PfinalClub\AsyncioGamekit\Exceptions\{RoomException, ManagerException};
use PfinalClub\AsyncioGamekit\Memory\{MemoryManager, MemoryManagerInterface};

/**
 * RoomManager 房间管理器
 * 管理多个游戏房间
 */
class RoomManager
{
    /** @var array<string, Room> 所有房间 */
    private array $rooms = [];
    
    /** @var array<string, string> 玩家ID到房间ID的映射 */
    private array $playerRoomMap = [];
    
    /** @var array<string, int> 已结束房间的时间戳 */
    private array $finishedRooms = [];
    
    /** @var int 清理间隔（秒） */
    private int $cleanupInterval = 300;
    
    /** @var array<string, array<string, array<string, Room>>> 按类名和状态分组的房间索引 */
    private array $roomIndex = [];
    
    /** @var MemoryManagerInterface 内存管理器 */
    private MemoryManagerInterface $memoryManager;
    
    /** @var int 最大房间数 */
    private int $maxRooms = 1000;

    /** @var array 实时统计信息 */
    private array $stats = [
        'total_rooms' => 0,
        'rooms_by_status' => [
            'waiting' => 0,
            'running' => 0,
            'finished' => 0,
        ],
    ];

    /**
     * 构造函数
     */
    public function __construct(?MemoryManagerInterface $memoryManager = null, int $maxRooms = 1000)
    {
        $this->memoryManager = $memoryManager ?? new MemoryManager(256, 0.8);
        $this->maxRooms = $maxRooms;
    }

    /**
     * 创建房间
     * 
     * @param string $roomClass 房间类名
     * @param string|null $roomId 房间ID，不指定则自动生成
     * @param array $config 房间配置
     * @return Room
     */
    public function createRoom(string $roomClass, ?string $roomId = null, array $config = []): Room
    {
        // 检查房间数限制
        if (count($this->rooms) >= $this->maxRooms) {
            // 尝试清理
            $this->cleanupFinishedRooms();
            
            // 如果还是超限，抛出异常
            if (count($this->rooms) >= $this->maxRooms) {
                throw ManagerException::maxLimitReached('rooms', $this->maxRooms);
            }
        }

        // 检查内存是否超限
        if ($this->memoryManager->isOverLimit()) {
            $this->memoryManager->gc();
            $this->cleanupFinishedRooms();
        }

        if ($roomId === null) {
            $roomId = uniqid('room_', true);
        }

        if (isset($this->rooms[$roomId])) {
            throw ManagerException::resourceAlreadyExists('Room', $roomId);
        }

        if (!is_subclass_of($roomClass, Room::class)) {
            throw ManagerException::invalidArgument('roomClass', "$roomClass must extend " . Room::class);
        }

        $room = new $roomClass($roomId, $config);
        $this->rooms[$roomId] = $room;
        
        // 添加到索引（新房间默认为 waiting 状态）
        $this->addToIndex($roomClass, 'waiting', $roomId, $room);
        
        // 更新实时统计
        $this->stats['total_rooms']++;
        $this->stats['rooms_by_status']['waiting']++;

        return $room;
    }

    /**
     * 获取房间
     */
    public function getRoom(string $roomId): ?Room
    {
        return $this->rooms[$roomId] ?? null;
    }

    /**
     * 获取所有房间
     * 
     * @return array<string, Room>
     */
    public function getRooms(): array
    {
        return $this->rooms;
    }

    /**
     * 删除房间（异步清理）
     */
    public function removeRoom(string $roomId): mixed
    {
        if (!isset($this->rooms[$roomId])) {
            return null;
        }

        $room = $this->rooms[$roomId];
        
        // 异步销毁房间，避免阻塞
        \PfinalClub\Asyncio\create_task(fn() => $room->destroy());
        
        // 立即清理玩家映射
        foreach ($room->getPlayers() as $player) {
            unset($this->playerRoomMap[$player->getId()]);
        }
        
        // 从索引中移除
        $roomClass = get_class($room);
        $status = $room->getStatus();
        $this->removeFromIndex($roomClass, $status, $roomId);

        // 更新实时统计
        $this->stats['total_rooms']--;
        if (isset($this->stats['rooms_by_status'][$status])) {
            $this->stats['rooms_by_status'][$status]--;
        }

        // 清理 finished 记录
        unset($this->finishedRooms[$roomId]);
        unset($this->rooms[$roomId]);
        
        return null;
    }

    /**
     * 玩家加入房间
     * 
     * @throws RoomException
     */
    public function joinRoom(Player $player, string $roomId): bool
    {
        $room = $this->getRoom($roomId);
        if (!$room) {
            throw RoomException::roomNotFound($roomId);
        }

        // 如果玩家已在其他房间，先离开
        if (isset($this->playerRoomMap[$player->getId()])) {
            $this->leaveRoom($player);
        }

        try {
            if ($room->addPlayer($player)) {
                $this->playerRoomMap[$player->getId()] = $roomId;
                return true;
            }
        } catch (RoomException $e) {
            throw $e;
        }

        return false;
    }

    /**
     * 玩家离开房间
     */
    public function leaveRoom(Player $player): bool
    {
        $playerId = $player->getId();
        
        if (!isset($this->playerRoomMap[$playerId])) {
            return false;
        }

        $roomId = $this->playerRoomMap[$playerId];
        $room = $this->getRoom($roomId);

        if ($room) {
            $room->removePlayer($playerId);
        }

        unset($this->playerRoomMap[$playerId]);
        return true;
    }

    /**
     * 获取玩家所在房间
     */
    public function getPlayerRoom(Player $player): ?Room
    {
        $playerId = $player->getId();
        
        if (!isset($this->playerRoomMap[$playerId])) {
            return null;
        }

        return $this->getRoom($this->playerRoomMap[$playerId]);
    }

    /**
     * 查找可用房间（有空位且未开始）
     */
    public function findAvailableRoom(string $roomClass): ?Room
    {
        // 使用索引快速查找 waiting 状态的房间
        $waitingRooms = $this->roomIndex[$roomClass]['waiting'] ?? [];
        
        foreach ($waitingRooms as $room) {
            if ($room->getPlayerCount() < $room->getMaxPlayers()) {
                return $room;
            }
        }
        
        return null;
    }

    /**
     * 快速匹配（自动创建或加入房间）
     */
    public function quickMatch(Player $player, string $roomClass, array $config = []): Room
    {
        // 先尝试找到可用房间
        $room = $this->findAvailableRoom($roomClass);
        
        // 没有可用房间则创建新房间
        if (!$room) {
            $room = $this->createRoom($roomClass, null, $config);
        }

        $this->joinRoom($player, $room->getId());
        
        return $room;
    }

    /**
     * 获取房间统计信息（实时维护，O(1) 复杂度）
     */
    public function getStats(): array
    {
        return [
            'total_rooms' => $this->stats['total_rooms'],
            'total_players' => count($this->playerRoomMap),
            'max_rooms' => $this->maxRooms,
            'rooms_by_status' => $this->stats['rooms_by_status'],
            'memory' => $this->memoryManager->getStats(),
        ];
    }
    
    /**
     * 获取内存管理器
     */
    public function getMemoryManager(): MemoryManagerInterface
    {
        return $this->memoryManager;
    }
    
    /**
     * 定期维护（应在主循环中调用）
     */
    public function periodicMaintenance(): void
    {
        $this->memoryManager->periodicCheck();
        $this->cleanupFinishedRooms();
    }

    /**
     * 清理已结束的房间
     * 
     * @return int 清理的房间数量
     */
    public function cleanupFinishedRooms(): int
    {
        $now = time();
        $cleaned = 0;
        
        foreach ($this->rooms as $roomId => $room) {
            if ($room->getStatus() === 'finished') {
                // 记录结束时间
                if (!isset($this->finishedRooms[$roomId])) {
                    $this->finishedRooms[$roomId] = $now;
                } 
                // 检查是否超过清理间隔
                elseif ($now - $this->finishedRooms[$roomId] > $this->cleanupInterval) {
                    $this->removeRoom($roomId);
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }

    /**
     * 设置清理间隔
     * 
     * @param int $seconds 清理间隔（秒）
     */
    public function setCleanupInterval(int $seconds): void
    {
        $this->cleanupInterval = $seconds;
    }

    /**
     * 获取清理间隔
     * 
     * @return int 清理间隔（秒）
     */
    public function getCleanupInterval(): int
    {
        return $this->cleanupInterval;
    }
    
    /**
     * 添加房间到索引
     * 
     * @param string $roomClass 房间类名
     * @param string $status 房间状态
     * @param string $roomId 房间ID
     * @param Room $room 房间对象
     */
    private function addToIndex(string $roomClass, string $status, string $roomId, Room $room): void
    {
        if (!isset($this->roomIndex[$roomClass])) {
            $this->roomIndex[$roomClass] = [];
        }
        if (!isset($this->roomIndex[$roomClass][$status])) {
            $this->roomIndex[$roomClass][$status] = [];
        }
        $this->roomIndex[$roomClass][$status][$roomId] = $room;
    }
    
    /**
     * 从索引中移除房间
     * 
     * @param string $roomClass 房间类名
     * @param string $status 房间状态
     * @param string $roomId 房间ID
     */
    private function removeFromIndex(string $roomClass, string $status, string $roomId): void
    {
        unset($this->roomIndex[$roomClass][$status][$roomId]);
        
        // 清理空的索引
        if (empty($this->roomIndex[$roomClass][$status])) {
            unset($this->roomIndex[$roomClass][$status]);
        }
        if (empty($this->roomIndex[$roomClass])) {
            unset($this->roomIndex[$roomClass]);
        }
    }
    
    /**
     * 更新房间在索引中的状态
     * 
     * @param Room $room 房间对象
     * @param string $oldStatus 旧状态
     * @param string $newStatus 新状态
     */
    public function updateRoomIndex(Room $room, string $oldStatus, string $newStatus): void
    {
        $roomClass = get_class($room);
        $roomId = $room->getId();
        
        $this->removeFromIndex($roomClass, $oldStatus, $roomId);
        $this->addToIndex($roomClass, $newStatus, $roomId, $room);
        
        // 更新实时统计（状态变化）
        if (isset($this->stats['rooms_by_status'][$oldStatus])) {
            $this->stats['rooms_by_status'][$oldStatus]--;
        }
        if (isset($this->stats['rooms_by_status'][$newStatus])) {
            $this->stats['rooms_by_status'][$newStatus]++;
        } else {
            // 如果是新的状态，初始化计数
            $this->stats['rooms_by_status'][$newStatus] = 1;
        }
    }
}

