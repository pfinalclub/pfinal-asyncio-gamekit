<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Room;

use PfinalClub\AsyncioGamekit\Persistence\PersistenceAdapterInterface;
use PfinalClub\AsyncioGamekit\Persistence\MemoryAdapter;

/**
 * 房间上下文实现类
 */
class RoomContext implements RoomContextInterface
{
    /** @var OptimizedRoom 所属房间 */
    protected OptimizedRoom $room;
    
    /** @var PlayerManagerInterface 玩家管理器 */
    protected PlayerManagerInterface $playerManager;
    
    /** @var LifecycleManagerInterface 生命周期管理器 */
    protected LifecycleManagerInterface $lifecycleManager;
    
    /** @var TimerManagerInterface 定时器管理器 */
    protected TimerManagerInterface $timerManager;
    
    /** @var PersistenceAdapterInterface 数据存储管理器 */
    protected PersistenceAdapterInterface $dataStorageManager;

    /**
     * @param OptimizedRoom $room 所属房间
     */
    public function __construct(OptimizedRoom $room)
    {
        $this->room = $room;
        
        // 初始化默认的管理器实现
        $this->playerManager = new PlayerManager($room);
        $this->lifecycleManager = new LifecycleManager($room);
        $this->timerManager = new TimerManager($room);
        $this->dataStorageManager = new MemoryAdapter();
    }

    /**
     * 获取玩家管理器
     */
    public function getPlayerManager(): PlayerManagerInterface
    {
        return $this->playerManager;
    }

    /**
     * 获取生命周期管理器
     */
    public function getLifecycleManager(): LifecycleManagerInterface
    {
        return $this->lifecycleManager;
    }

    /**
     * 获取定时器管理器
     */
    public function getTimerManager(): TimerManagerInterface
    {
        return $this->timerManager;
    }

    /**
     * 获取数据存储管理器
     */
    public function getDataStorageManager(): PersistenceAdapterInterface
    {
        return $this->dataStorageManager;
    }
    
    /**
     * 设置玩家管理器
     */
    public function setPlayerManager(PlayerManagerInterface $playerManager): void
    {
        $this->playerManager = $playerManager;
    }
    
    /**
     * 设置生命周期管理器
     */
    public function setLifecycleManager(LifecycleManagerInterface $lifecycleManager): void
    {
        $this->lifecycleManager = $lifecycleManager;
    }
    
    /**
     * 设置定时器管理器
     */
    public function setTimerManager(TimerManagerInterface $timerManager): void
    {
        $this->timerManager = $timerManager;
    }
    
    /**
     * 设置数据存储管理器
     */
    public function setDataStorageManager(PersistenceAdapterInterface $dataStorageManager): void
    {
        $this->dataStorageManager = $dataStorageManager;
    }
    
    /**
     * 获取房间配置
     */
    public function getConfig(): \PfinalClub\AsyncioGamekit\Config\ConfigInterface
    {
        return $this->room->getConfig();
    }
    
    /**
     * 设置房间配置
     */
    public function setConfig(\PfinalClub\AsyncioGamekit\Config\ConfigInterface $config): void
    {
        $this->room->setConfig($config);
    }
}