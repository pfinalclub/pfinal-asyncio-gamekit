<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Manager;

use PfinalClub\AsyncioGamekit\Room\RoomObserverInterface;
use PfinalClub\AsyncioGamekit\Room\RoomInterface;
use PfinalClub\AsyncioGamekit\RoomManager;

/**
 * RoomManager 观察者
 * 自动更新 RoomManager 的索引当房间状态变化时
 */
class RoomManagerObserver implements RoomObserverInterface
{
    public function __construct(
        private RoomManager $roomManager
    ) {}

    /**
     * 当房间状态改变时，自动更新索引
     */
    public function onRoomStatusChanged(RoomInterface $room, string $oldStatus, string $newStatus): void
    {
        // 自动更新 RoomManager 的索引
        $this->roomManager->updateRoomIndex($room, $oldStatus, $newStatus);
    }

    /**
     * 当玩家加入房间时（可用于统计等）
     */
    public function onPlayerJoined(RoomInterface $room, string $playerId): void
    {
        // 可以在这里记录统计信息或触发其他逻辑
    }

    /**
     * 当玩家离开房间时（可用于统计等）
     */
    public function onPlayerLeft(RoomInterface $room, string $playerId): void
    {
        // 可以在这里记录统计信息或触发其他逻辑
    }
}

