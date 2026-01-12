<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Manager;

use PfinalClub\AsyncioGamekit\Room\Room;
use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\Exceptions\ManagerException;

/**
 * 房间注册表
 * 
 * 负责房间的存储、检索和索引管理
 */
class RoomRegistry
{
    /** @var array<string, Room> 所有房间 */
    private array $rooms = [];
    
    /** @var array<string, string> 玩家ID到房间ID的映射 */
    private array $playerRoomMap = [];
    
    /** @var array<string, array<string, array<string, Room>>> 按类名和状态分组的房间索引 */
    private array $roomIndex = [];
    
    /** @var int 最大房间数 */
    private int $maxRooms;

    public function __construct(int $maxRooms = 1000)
    {
        $this->maxRooms = $maxRooms;
    }

    /**
     * 注册房间
     * 
     * @param Room $room
     * @throws ManagerException
     */
    public function register(Room $room): void
    {
        $roomId = $room->getId();
        
        // 检查房间数限制
        if (count($this->rooms) >= $this->maxRooms) {
            throw ManagerException::maxLimitReached('rooms', $this->maxRooms);
        }
        
        // 检查房间是否已存在
        if (isset($this->rooms[$roomId])) {
            throw ManagerException::resourceAlreadyExists('Room', $roomId);
        }
        
        $this->rooms[$roomId] = $room;
        
        // 添加到索引
        $this->addToIndex($room->getClassName(), RoomStatus::WAITING, $roomId, $room);
    }

    /**
     * 注销房间
     * 
     * @param string $roomId
     */
    public function unregister(string $roomId): void
    {
        if (!isset($this->rooms[$roomId])) {
            return;
        }
        
        $room = $this->rooms[$roomId];
        
        // 从索引中移除
        $this->removeFromIndex($room->getClassName(), $room->getStatus(), $roomId);
        
        // 移除所有玩家映射
        foreach ($room->getPlayers() as $player) {
            unset($this->playerRoomMap[$player->getId()]);
        }
        
        unset($this->rooms[$roomId]);
    }

    /**
     * 获取房间
     * 
     * @param string $roomId
     * @return Room|null
     */
    public function get(string $roomId): ?Room
    {
        return $this->rooms[$roomId] ?? null;
    }

    /**
     * 检查房间是否存在
     * 
     * @param string $roomId
     * @return bool
     */
    public function has(string $roomId): bool
    {
        return isset($this->rooms[$roomId]);
    }

    /**
     * 获取所有房间
     * 
     * @return array<string, Room>
     */
    public function getAll(): array
    {
        return $this->rooms;
    }

    /**
     * 记录玩家所在房间
     * 
     * @param Player $player
     * @param string $roomId
     */
    public function registerPlayer(Player $player, string $roomId): void
    {
        $this->playerRoomMap[$player->getId()] = $roomId;
    }

    /**
     * 移除玩家映射
     * 
     * @param Player $player
     */
    public function unregisterPlayer(Player $player): void
    {
        unset($this->playerRoomMap[$player->getId()]);
    }

    /**
     * 获取玩家所在房间
     * 
     * @param Player $player
     * @return Room|null
     */
    public function getRoomByPlayer(Player $player): ?Room
    {
        $roomId = $this->playerRoomMap[$player->getId()] ?? null;
        return $roomId ? $this->get($roomId) : null;
    }

    /**
     * 按类名和状态查找房间
     * 
     * @param string $roomClass
     * @param string $status
     * @return array<Room>
     */
    public function findByClassAndStatus(string $roomClass, string $status = RoomStatus::WAITING): array
    {
        return $this->roomIndex[$roomClass][$status] ?? [];
    }

    /**
     * 更新房间索引
     * 
     * @param Room $room
     * @param string $oldStatus
     * @param string $newStatus
     */
    public function updateIndex(Room $room, string $oldStatus, string $newStatus): void
    {
        $roomClass = $room->getClassName();
        $roomId = $room->getId();
        
        $this->removeFromIndex($roomClass, $oldStatus, $roomId);
        $this->addToIndex($roomClass, $newStatus, $roomId, $room);
    }

    /**
     * 获取统计信息
     * 
     * @return array
     */
    public function getStats(): array
    {
        $stats = [
            'total_rooms' => count($this->rooms),
            'total_players' => count($this->playerRoomMap),
            'max_rooms' => $this->maxRooms,
            'rooms_by_status' => [
                RoomStatus::WAITING => 0,
                RoomStatus::RUNNING => 0,
                RoomStatus::FINISHED => 0,
            ],
        ];

        foreach ($this->rooms as $room) {
            $status = $room->getStatus();
            if (isset($stats['rooms_by_status'][$status])) {
                $stats['rooms_by_status'][$status]++;
            }
        }

        return $stats;
    }

    /**
     * 添加房间到索引
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
}

