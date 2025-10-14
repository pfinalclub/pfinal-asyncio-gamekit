<?php

namespace PfinalClub\AsyncioGamekit\Persistence;

use PfinalClub\AsyncioGamekit\Room;

/**
 * 房间状态管理器
 * 负责房间状态的持久化和恢复
 */
class RoomStateManager
{
    private PersistenceAdapterInterface $adapter;
    private string $prefix = 'room:';

    public function __construct(PersistenceAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * 保存房间状态
     */
    public function saveRoomState(Room $room): bool
    {
        $key = $this->prefix . $room->getId();
        $state = $room->toArray();
        
        // 默认保存24小时
        return $this->adapter->set($key, $state, 86400);
    }

    /**
     * 获取房间状态
     */
    public function getRoomState(string $roomId): ?array
    {
        $key = $this->prefix . $roomId;
        $state = $this->adapter->get($key);
        
        return $state !== null ? $state : null;
    }

    /**
     * 删除房间状态
     */
    public function deleteRoomState(string $roomId): bool
    {
        $key = $this->prefix . $roomId;
        return $this->adapter->delete($key);
    }

    /**
     * 检查房间状态是否存在
     */
    public function hasRoomState(string $roomId): bool
    {
        $key = $this->prefix . $roomId;
        return $this->adapter->has($key);
    }

    /**
     * 保存玩家会话数据
     */
    public function savePlayerSession(string $playerId, array $data, int $ttl = 3600): bool
    {
        $key = 'player:session:' . $playerId;
        return $this->adapter->set($key, $data, $ttl);
    }

    /**
     * 获取玩家会话数据
     */
    public function getPlayerSession(string $playerId): ?array
    {
        $key = 'player:session:' . $playerId;
        $data = $this->adapter->get($key);
        
        return $data !== null ? $data : null;
    }

    /**
     * 保存游戏统计数据
     */
    public function saveGameStats(string $statsKey, array $stats): bool
    {
        $key = 'stats:' . $statsKey;
        return $this->adapter->set($key, $stats, 0); // 永不过期
    }

    /**
     * 获取游戏统计数据
     */
    public function getGameStats(string $statsKey): ?array
    {
        $key = 'stats:' . $statsKey;
        $stats = $this->adapter->get($key);
        
        return $stats !== null ? $stats : null;
    }
}

