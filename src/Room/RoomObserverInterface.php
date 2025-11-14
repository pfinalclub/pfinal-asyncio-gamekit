<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Room;

/**
 * Room 观察者接口
 * 
 * 实现此接口以监听 Room 的状态变化
 */
interface RoomObserverInterface
{
    /**
     * 当房间状态改变时调用
     * 
     * @param RoomInterface $room 房间对象
     * @param string $oldStatus 旧状态
     * @param string $newStatus 新状态
     */
    public function onRoomStatusChanged(RoomInterface $room, string $oldStatus, string $newStatus): void;
    
    /**
     * 当玩家加入房间时调用
     * 
     * @param RoomInterface $room 房间对象
     * @param string $playerId 玩家ID
     */
    public function onPlayerJoined(RoomInterface $room, string $playerId): void;
    
    /**
     * 当玩家离开房间时调用
     * 
     * @param RoomInterface $room 房间对象
     * @param string $playerId 玩家ID
     */
    public function onPlayerLeft(RoomInterface $room, string $playerId): void;
}

