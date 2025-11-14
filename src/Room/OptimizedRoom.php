<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Room;

use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\Persistence\PersistenceAdapterInterface;
use PfinalClub\AsyncioGamekit\Config\ConfigInterface;
use PfinalClub\AsyncioGamekit\Config\Config;

/**
 * 优化后的Room类
 * 将trait转换为独立的服务类
 */
abstract class OptimizedRoom
{
    /** @var string 房间ID */
    protected string $id;
    
    /** @var ConfigInterface 房间配置 */
    protected ConfigInterface $config;
    
    /** @var int|null 缓存的最大玩家数 */
    private ?int $cachedMaxPlayers = null;
    
    /** @var int|null 缓存的最小玩家数 */
    private ?int $cachedMinPlayers = null;
    
    /** @var RoomContextInterface 房间上下文 */
    protected RoomContextInterface $context;

    /**
     * @param string $id 房间ID
     * @param array|ConfigInterface $config 房间配置
     */
    public function __construct(string $id, array|ConfigInterface $config = [])
    {
        $this->id = $id;
        
        // 处理配置
        if (is_array($config)) {
            // 合并默认配置并创建Config实例
            $mergedConfig = array_merge($this->getDefaultConfig(), $config);
            $this->config = new Config($mergedConfig);
        } else {
            // 创建包含默认配置的Config实例并合并输入配置
            $defaultConfig = new Config($this->getDefaultConfig());
            $defaultConfig->load($config->all());
            $this->config = $defaultConfig;
        }
        
        // 缓存常用配置
        $this->cachedMaxPlayers = $this->config->get('max_players', 4);
        $this->cachedMinPlayers = $this->config->get('min_players', 2);
        
        // 初始化房间上下文
        $this->context = new RoomContext($this);
    }

    /**
     * 获取默认配置（子类可重写）
     */
    protected function getDefaultConfig(): array
    {
        return [
            'max_players' => 4,
            'min_players' => 2,
            'auto_start' => false,
        ];
    }
    
    /**
     * 获取房间配置
     */
    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    /**
     * 获取房间ID
     */
    public function getId(): string
    {
        return $this->id;
    }
    
    /**
     * 获取房间上下文
     */
    public function getContext(): RoomContextInterface
    {
        return $this->context;
    }
    
    /**
     * 设置房间上下文
     */
    public function setContext(RoomContextInterface $context): void
    {
        $this->context = $context;
    }
    
    /**
     * 获取玩家管理服务
     */
    public function getPlayerManager(): PlayerManagerInterface
    {
        return $this->context->getPlayerManager();
    }
    
    /**
     * 获取生命周期管理服务
     */
    public function getLifecycleManager(): LifecycleManagerInterface
    {
        return $this->context->getLifecycleManager();
    }
    
    /**
     * 获取定时器管理服务
     */
    public function getTimerManager(): TimerManagerInterface
    {
        return $this->context->getTimerManager();
    }
    
    /**
     * 获取数据存储服务
     */
    public function getDataStorageManager(): PersistenceAdapterInterface
    {
        return $this->context->getDataStorageManager();
    }
    
    // -----------------------
    // 委托方法：将原trait的方法委托给对应服务
    // -----------------------
    
    /**
     * 添加玩家（委托给PlayerManager）
     */
    public function addPlayer(Player $player): bool
    {
        return $this->context->getPlayerManager()->addPlayer($player);
    }
    
    /**
     * 移除玩家（委托给PlayerManager）
     */
    public function removePlayer(string $playerId): bool
    {
        return $this->context->getPlayerManager()->removePlayer($playerId);
    }
    
    /**
     * 获取玩家（委托给PlayerManager）
     */
    public function getPlayer(string $playerId): ?Player
    {
        return $this->context->getPlayerManager()->getPlayer($playerId);
    }
    
    /**
     * 获取所有玩家（委托给PlayerManager）
     */
    public function getPlayers(): array
    {
        return $this->context->getPlayerManager()->getPlayers();
    }
    
    /**
     * 获取玩家数量（委托给PlayerManager）
     */
    public function getPlayerCount(): int
    {
        return $this->context->getPlayerManager()->getPlayerCount();
    }
    
    /**
     * 获取房间状态（委托给LifecycleManager）
     */
    public function getStatus(): string
    {
        return $this->context->getLifecycleManager()->getStatus();
    }
    
    /**
     * 检查是否可以开始游戏（委托给LifecycleManager）
     */
    public function canStart(): bool
    {
        return $this->context->getLifecycleManager()->canStart();
    }
    
    /**
     * 启动房间游戏逻辑（委托给LifecycleManager）
     */
    public function start(): mixed
    {
        return $this->context->getLifecycleManager()->start();
    }
    
    /**
     * 销毁房间（委托给LifecycleManager）
     */
    public function destroy(): mixed
    {
        return $this->context->getLifecycleManager()->destroy();
    }
    
    /**
     * 添加定时任务（委托给TimerManager）
     */
    protected function addTimer(float $interval, callable $callback, bool $repeat = false): ?int
    {
        return $this->context->getTimerManager()->addTimer($interval, $callback, $repeat);
    }
    
    /**
     * 删除定时任务（委托给TimerManager）
     */
    protected function removeTimer(int $timerId): void
    {
        $this->context->getTimerManager()->removeTimer($timerId);
    }
    
    /**
     * 清除所有定时器（委托给TimerManager）
     */
    protected function clearAllTimers(): void
    {
        $this->context->getTimerManager()->clearAllTimers();
    }
    
    // -----------------------
    // 钩子方法：由服务类调用
    // -----------------------
    
    /**
     * 玩家加入时调用（子类可重写）
     */
    public function onPlayerJoin(Player $player): void
    {}
    
    /**
     * 玩家离开时调用（子类可重写）
     */
    public function onPlayerLeave(Player $player): void
    {}
    
    /**
     * 房间创建时调用（子类可重写）
     */
    public function onCreate(): void
    {}
    
    /**
     * 房间开始时调用（子类可重写）
     */
    public function onStart(): void
    {}
    
    /**
     * 房间结束时调用（子类可重写）
     */
    public function onFinish(): void
    {}
    
    /**
     * 房间销毁时调用（子类可重写）
     */
    public function onDestroy(): void
    {}
    
    /**
     * 游戏逻辑主循环（必须由子类实现）
     */
    abstract public function run(): mixed;
    
    /**
     * 广播消息给房间内所有玩家
     */
    abstract public function broadcast(string $event, mixed $data = null, ?Player $except = null): void;
}