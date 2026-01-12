<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Room;

use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\Room\Traits\{
    PlayerManagement,
    LifecycleManagement,
    TimerManagement,
    DataStorage
};
use PfinalClub\AsyncioGamekit\Utils\JsonEncoder;

/**
 * Room 房间基类
 * 提供异步游戏逻辑编排能力
 * 支持观察者模式自动通知状态变化
 */
abstract class Room implements RoomInterface
{
    use PlayerManagement;
    use LifecycleManagement;
    use TimerManagement;
    use DataStorage;
    
    /** @var array<RoomObserverInterface> 观察者列表 */
    private array $observers = [];

    /** @var string 房间ID */
    protected string $id;
    
    /** @var array 房间配置 */
    protected array $config = [];
    
    /** @var int|null 缓存的最大玩家数 */
    private ?int $cachedMaxPlayers = null;
    
    /** @var int|null 缓存的最小玩家数 */
    private ?int $cachedMinPlayers = null;

    /** @var array|null 缓存的完整数组表示（包含玩家） */
    private ?array $cachedArrayFull = null;

    /** @var array|null 缓存的完整数组表示（不包含玩家） */
    private ?array $cachedArrayNoPlayers = null;

    /** @var array|null 缓存的轻量级数组表示 */
    private ?array $cachedArrayLight = null;
    
    /** @var array|null 【性能优化】缓存的玩家列表序列化结果 */
    private ?array $cachedPlayersList = null;

    /** @var bool 数据是否已修改（脏标记） */
    private bool $isDirty = true;
    
    /** @var bool 【性能优化】玩家列表是否已修改（脏标记） */
    private bool $isPlayersListDirty = true;
    
    /** @var string|null 缓存的类名 */
    private ?string $cachedClassName = null;

    /**
     * @param string $id 房间ID
     * @param array $config 房间配置
     */
    public function __construct(string $id, array $config = [])
    {
        $this->id = $id;
        $this->config = array_merge($this->getDefaultConfig(), $config);
        
        // 缓存常用配置
        $this->cachedMaxPlayers = $this->config['max_players'] ?? 4;
        $this->cachedMinPlayers = $this->config['min_players'] ?? 2;
    }
    
    /**
     * 获取类名（带缓存）
     * 
     * @return string 类名
     */
    public function getClassName(): string
    {
        if ($this->cachedClassName === null) {
            $this->cachedClassName = get_class($this);
        }
        return $this->cachedClassName;
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
     * 获取房间ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * 获取最大玩家数
     */
    public function getMaxPlayers(): int
    {
        return $this->cachedMaxPlayers;
    }

    /**
     * 获取配置
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * 广播消息给房间内所有玩家
     */
    public function broadcast(string $event, mixed $data = null, ?Player $except = null): void
    {
        if (empty($this->players)) {
            return;
        }
        
        // 预先编码一次消息，避免每个玩家都重复编码
        try {
            $message = JsonEncoder::encodeMessage($event, $data);
        } catch (\JsonException $e) {
            \PfinalClub\AsyncioGamekit\Logger\LoggerFactory::error("Failed to encode broadcast message", [
                'event' => $event,
                'room_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return;
        }
        
        $failedPlayers = [];
        $exceptId = $except?->getId();
        
        foreach ($this->players as $player) {
            if ($exceptId && $player->getId() === $exceptId) {
                continue;
            }
            
            // 只有真正有连接的玩家才发送并检查失败
            $connection = $player->getConnection();
            if ($connection && method_exists($connection, 'send')) {
                // 使用预编码的消息发送
                if (!$player->send($message, null, true)) {
                    // 有连接但发送失败，说明连接已断开
                    $failedPlayers[] = $player->getId();
                }
            }
            // 没有连接的玩家（如测试环境）直接跳过，不移除
        }
        
        // 批量移除发送失败的玩家（连接已断开）
        if (!empty($failedPlayers)) {
            foreach ($failedPlayers as $playerId) {
                \PfinalClub\AsyncioGamekit\Logger\LoggerFactory::warning("Removing player due to send failure", [
                    'player_id' => $playerId,
                    'room_id' => $this->id,
                ]);
                $this->removePlayer($playerId);
            }
        }
    }

    /**
     * 异步广播（延迟执行）
     */
    public function broadcastAsync(string $event, mixed $data = null, float $delay = 0): mixed
    {
        if ($delay > 0) {
            \PfinalClub\Asyncio\sleep($delay);
        }
        $this->broadcast($event, $data);
        
        return null;
    }

    /**
     * 处理玩家消息（子类可重写）
     */
    public function onPlayerMessage(Player $player, string $event, mixed $data): mixed
    {
        return null;
    }

    /**
     * 清除缓存（当房间状态改变时调用）
     */
    protected function invalidateCache(): void
    {
        $this->isDirty = true;
        $this->cachedArrayFull = null;
        $this->cachedArrayNoPlayers = null;
        $this->cachedArrayLight = null;
        // 玩家列表不在此处失效，有专门的方法
    }
    
    /**
     * 【性能优化】清除玩家列表缓存（当玩家加入/离开时调用）
     */
    public function invalidatePlayersListCache(): void
    {
        $this->isPlayersListDirty = true;
        $this->cachedPlayersList = null;
        // 玩家列表变化也会影响完整数组缓存
        $this->cachedArrayFull = null;
        $this->cachedArrayLight = null;
    }

    /**
     * 转换为数组（完整版，带缓存）
     * 
     * @param bool $includePlayers 是否包含玩家详细信息
     */
    public function toArray(bool $includePlayers = true): array
    {
        if ($includePlayers) {
            // 包含玩家的完整版
            if (!$this->isDirty && $this->cachedArrayFull !== null) {
                return $this->cachedArrayFull;
            }
            
            // 【性能优化】使用独立缓存的玩家列表
            $this->cachedArrayFull = [
                'id' => $this->id,
                'status' => $this->status,
                'player_count' => count($this->players),
                'config' => $this->config,
                'data' => $this->data,
                'players' => $this->getPlayersArray() // 使用缓存方法
            ];
            
            $this->isDirty = false;
            return $this->cachedArrayFull;
        } else {
            // 不包含玩家的版本
            if (!$this->isDirty && $this->cachedArrayNoPlayers !== null) {
                return $this->cachedArrayNoPlayers;
            }
            
            $this->cachedArrayNoPlayers = [
                'id' => $this->id,
                'status' => $this->status,
                'player_count' => count($this->players),
                'config' => $this->config,
                'data' => $this->data
            ];
            
            $this->isDirty = false;
            return $this->cachedArrayNoPlayers;
        }
    }
    
    /**
     * 【性能优化】获取玩家列表的序列化结果（带缓存）
     * 
     * 避免重复序列化玩家列表，尤其在频繁调用 toArray() 时
     */
    private function getPlayersArray(): array
    {
        if (!$this->isPlayersListDirty && $this->cachedPlayersList !== null) {
            return $this->cachedPlayersList;
        }
        
        // 序列化所有玩家
        $this->cachedPlayersList = array_map(fn($p) => $p->toArray(), $this->players);
        $this->isPlayersListDirty = false;
        
        return $this->cachedPlayersList;
    }

    /**
     * 转换为轻量级数组（不包含玩家详细信息，带缓存）
     */
    public function toArrayLight(): array
    {
        // 轻量级版本缓存独立，只在玩家数变化或状态变化时失效
        if (!$this->isDirty && $this->cachedArrayLight !== null) {
            return $this->cachedArrayLight;
        }
        
        $this->cachedArrayLight = [
            'id' => $this->id,
            'status' => $this->status,
            'player_count' => count($this->players),
            'max_players' => $this->cachedMaxPlayers,
            'min_players' => $this->cachedMinPlayers,
        ];
        
        // 轻量级不设置 isDirty = false，因为它不包含完整数据
        return $this->cachedArrayLight;
    }

    /**
     * 添加观察者
     */
    public function attach(RoomObserverInterface $observer): void
    {
        $this->observers[] = $observer;
    }

    /**
     * 移除观察者
     */
    public function detach(RoomObserverInterface $observer): void
    {
        $this->observers = array_filter(
            $this->observers,
            fn($obs) => $obs !== $observer
        );
    }

    /**
     * 通知观察者：状态变化
     */
    protected function notifyStatusChanged(string $oldStatus, string $newStatus): void
    {
        foreach ($this->observers as $observer) {
            $observer->onRoomStatusChanged($this, $oldStatus, $newStatus);
        }
    }

    /**
     * 通知观察者：玩家加入
     */
    protected function notifyPlayerJoined(string $playerId): void
    {
        foreach ($this->observers as $observer) {
            $observer->onPlayerJoined($this, $playerId);
        }
    }

    /**
     * 通知观察者：玩家离开
     */
    protected function notifyPlayerLeft(string $playerId): void
    {
        foreach ($this->observers as $observer) {
            $observer->onPlayerLeft($this, $playerId);
        }
    }
}

