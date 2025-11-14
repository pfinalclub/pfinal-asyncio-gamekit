<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Room;

use PfinalClub\AsyncioGamekit\Persistence\PersistenceAdapterInterface;
use PfinalClub\AsyncioGamekit\Config\ConfigInterface;

/**
 * 房间上下文接口
 */
interface RoomContextInterface
{
    /**
     * 获取玩家管理器
     * @return PlayerManagerInterface
     */
    public function getPlayerManager(): PlayerManagerInterface;
    
    /**
     * 设置玩家管理器
     */
    public function setPlayerManager(PlayerManagerInterface $playerManager): void;
    
    /**
     * 获取生命周期管理器
     * @return LifecycleManagerInterface
     */
    public function getLifecycleManager(): LifecycleManagerInterface;
    
    /**
     * 设置生命周期管理器
     */
    public function setLifecycleManager(LifecycleManagerInterface $lifecycleManager): void;
    
    /**
     * 获取定时器管理器
     * @return TimerManagerInterface
     */
    public function getTimerManager(): TimerManagerInterface;
    
    /**
     * 设置定时器管理器
     */
    public function setTimerManager(TimerManagerInterface $timerManager): void;
    
    /**
     * 获取数据存储管理器
     * @return PersistenceAdapterInterface
     */
    public function getDataStorageManager(): PersistenceAdapterInterface;
    
    /**
     * 设置数据存储管理器
     */
    public function setDataStorageManager(PersistenceAdapterInterface $dataStorageManager): void;
    
    /**
     * 获取房间配置
     * @return ConfigInterface
     */
    public function getConfig(): ConfigInterface;
    
    /**
     * 设置房间配置
     */
    public function setConfig(ConfigInterface $config): void;
}