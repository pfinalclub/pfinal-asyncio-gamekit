<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit;

use PfinalClub\AsyncioGamekit\Config\ConfigInterface;
use PfinalClub\AsyncioGamekit\Room\RoomManagerInterface;
use PfinalClub\AsyncioGamekit\Persistence\PersistenceAdapterInterface;
use PfinalClub\AsyncioGamekit\Container\ContainerInterface;

/**
 * 游戏服务器接口
 */
interface GameServerInterface
{
    /**
     * 启动服务器
     * 
     * @return void
     */
    public function start(): void;

    /**
     * 停止服务器
     * 
     * @return void
     */
    public function stop(): void;

    /**
     * 重启服务器
     * 
     * @return void
     */
    public function restart(): void;

    /**
     * 获取房间管理器
     * 
     * @return RoomManagerInterface 房间管理器
     */
    public function getRoomManager(): RoomManagerInterface;

    /**
     * 设置房间管理器
     * 
     * @param RoomManagerInterface $roomManager 房间管理器
     * @return void
     */
    public function setRoomManager(RoomManagerInterface $roomManager): void;

    /**
     * 获取配置
     * 
     * @return ConfigInterface 配置对象
     */
    public function getConfig(): ConfigInterface;

    /**
     * 设置配置
     * 
     * @param array|ConfigInterface $config 配置数组或对象
     * @return void
     */
    public function setConfig(array|ConfigInterface $config): void;

    /**
     * 获取容器
     * 
     * @return ContainerInterface 容器对象
     */
    public function getContainer(): ContainerInterface;

    /**
     * 设置容器
     * 
     * @param ContainerInterface $container 容器对象
     * @return void
     */
    public function setContainer(ContainerInterface $container): void;

    /**
     * 获取持久化适配器
     * 
     * @return PersistenceAdapterInterface|null 持久化适配器
     */
    public function getPersistenceAdapter(): ?PersistenceAdapterInterface;

    /**
     * 设置持久化适配器
     * 
     * @param PersistenceAdapterInterface $adapter 持久化适配器
     * @return void
     */
    public function setPersistenceAdapter(PersistenceAdapterInterface $adapter): void;

    /**
     * 记录错误
     * 
     * @param \Throwable $e 异常对象
     * @return void
     */
    public function logError(\Throwable $e): void;
}