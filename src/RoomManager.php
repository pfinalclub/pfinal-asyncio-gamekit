<?php

namespace PfinalClub\AsyncioGamekit;

use PfinalClub\AsyncioGamekit\Exceptions\RoomException;

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
        if ($roomId === null) {
            $roomId = uniqid('room_', true);
        }

        if (isset($this->rooms[$roomId])) {
            throw new \RuntimeException("Room {$roomId} already exists");
        }

        if (!is_subclass_of($roomClass, Room::class)) {
            throw new \InvalidArgumentException("$roomClass must extend " . Room::class);
        }

        $room = new $roomClass($roomId, $config);
        $this->rooms[$roomId] = $room;

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
     * 删除房间
     */
    public function removeRoom(string $roomId): mixed
    {
        if (!isset($this->rooms[$roomId])) {
            return null;
        }

        $room = $this->rooms[$roomId];
        
        // 销毁房间
        $room->destroy();
        
        // 清理玩家映射
        foreach ($room->getPlayers() as $player) {
            unset($this->playerRoomMap[$player->getId()]);
        }

        unset($this->rooms[$roomId]);
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
        foreach ($this->rooms as $room) {
            if (
                get_class($room) === $roomClass 
                && $room->getStatus() === 'waiting'
                && $room->getPlayerCount() < $room->toArray()['config']['max_players']
            ) {
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
     * 获取房间统计信息
     */
    public function getStats(): array
    {
        $stats = [
            'total_rooms' => count($this->rooms),
            'total_players' => count($this->playerRoomMap),
            'rooms_by_status' => [
                'waiting' => 0,
                'running' => 0,
                'finished' => 0,
            ]
        ];

        foreach ($this->rooms as $room) {
            $status = $room->getStatus();
            if (isset($stats['rooms_by_status'][$status])) {
                $stats['rooms_by_status'][$status]++;
            }
        }

        return $stats;
    }
}

