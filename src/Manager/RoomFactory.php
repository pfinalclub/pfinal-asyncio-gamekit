<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Manager;

use PfinalClub\AsyncioGamekit\Room;
use PfinalClub\AsyncioGamekit\Exceptions\ManagerException;

/**
 * 房间工厂
 * 
 * 负责房间的创建和验证
 */
class RoomFactory
{
    /**
     * 创建房间实例
     * 
     * @param string $roomClass 房间类名
     * @param string $roomId 房间ID
     * @param array $config 房间配置
     * @return Room
     * @throws ManagerException
     */
    public function create(string $roomClass, string $roomId, array $config = []): Room
    {
        // 验证房间类
        if (!is_subclass_of($roomClass, Room::class)) {
            throw ManagerException::invalidArgument('roomClass', "$roomClass must extend " . Room::class);
        }

        // 创建房间实例
        return new $roomClass($roomId, $config);
    }

    /**
     * 验证房间类是否有效
     * 
     * @param string $roomClass 房间类名
     * @return bool
     */
    public function isValidRoomClass(string $roomClass): bool
    {
        return is_subclass_of($roomClass, Room::class);
    }
}

