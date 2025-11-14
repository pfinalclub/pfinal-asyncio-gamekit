<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Room;

use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\Exceptions\RoomException;
use PfinalClub\AsyncioGamekit\Persistence\PersistenceAdapterInterface;

/**
 * 房间管理器接口
 */
interface RoomManagerInterface
{
    /**
     * 创建房间
     * 
     * @param string $roomClass 房间类名
     * @param string|null $roomId 房间ID
     * @param array $config 房间配置
     * @return Room 房间实例
     * @throws RoomException
     */
    public function createRoom(string $roomClass, ?string $roomId = null, array $config = []): Room;

    /**
     * 获取房间
     * 
     * @param string $roomId 房间ID
     * @return Room|null 房间实例
     */
    public function getRoom(string $roomId): ?Room;

    /**
     * 获取所有房间
     * 
     * @return array<string, Room> 房间数组
     */
    public function getRooms(): array;

    /**
     * 删除房间
     * 
     * @param string $roomId 房间ID
     * @return bool 是否成功
     */
    public function deleteRoom(string $roomId): bool;

    /**
     * 获取玩家所在房间
     *
     * @param Player $player 玩家实例
     * @return Room|null 房间实例
     */
    public function getPlayerRoom(Player $player): ?Room;

    /**
     * 获取统计信息
     *
     * @return array 统计数据
     */
    public function getStats(): array;

    /**
     * 玩家加入房间
     * 
     * @param Player $player 玩家实例
     * @param string $roomId 房间ID
     * @return bool 是否成功
     * @throws RoomException
     */
    public function joinRoom(Player $player, string $roomId): bool;

    /**
     * 玩家离开房间
     * 
     * @param Player $player 玩家实例
     * @return bool 是否成功
     */
    public function leaveRoom(Player $player): bool;

    /**
     * 快速匹配
     * 
     * @param Player $player 玩家实例
     * @param string $roomClass 房间类名
     * @param array $config 房间配置
     * @return Room 房间实例
     * @throws RoomException
     */
    public function quickMatch(Player $player, string $roomClass, array $config = []): Room;

    /**
     * 玩家离开所有房间
     * 
     * @param Player $player 玩家实例
     * @return bool 是否成功
     */
    public function playerLeaveAllRooms(Player $player): bool;

    /**
     * 设置持久化适配器
     * 
     * @param PersistenceAdapterInterface $adapter 持久化适配器
     */
    public function setPersistenceAdapter(PersistenceAdapterInterface $adapter): void;

    /**
     * 获取持久化适配器
     * 
     * @return PersistenceAdapterInterface 持久化适配器
     */
    public function getPersistenceAdapter(): PersistenceAdapterInterface;

    /**
     * 定期维护
     */
    public function periodicMaintenance(): void;

    /**
     * 关闭所有房间
     */
    public function shutdown(): void;
}