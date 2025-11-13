<?php

namespace PfinalClub\AsyncioGamekit\Room;

use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\Room\Traits\{
    PlayerManagement,
    LifecycleManagement,
    TimerManagement,
    DataStorage
};

/**
 * Room 房间基类
 * 提供异步游戏逻辑编排能力
 */
abstract class Room
{
    use PlayerManagement;
    use LifecycleManagement;
    use TimerManagement;
    use DataStorage;

    /** @var string 房间ID */
    protected string $id;
    
    /** @var array 房间配置 */
    protected array $config = [];
    
    /** @var int|null 缓存的最大玩家数 */
    private ?int $cachedMaxPlayers = null;
    
    /** @var int|null 缓存的最小玩家数 */
    private ?int $cachedMinPlayers = null;

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
            $message = json_encode([
                'event' => $event,
                'data' => $data,
                'timestamp' => microtime(true)
            ], JSON_THROW_ON_ERROR);
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
     * 转换为数组（完整版）
     * 
     * @param bool $includePlayers 是否包含玩家详细信息
     */
    public function toArray(bool $includePlayers = true): array
    {
        $result = [
            'id' => $this->id,
            'status' => $this->status,
            'player_count' => count($this->players),
            'config' => $this->config,
            'data' => $this->data
        ];
        
        if ($includePlayers) {
            $result['players'] = array_map(fn($p) => $p->toArray(), $this->players);
        }
        
        return $result;
    }

    /**
     * 转换为轻量级数组（不包含玩家详细信息）
     */
    public function toArrayLight(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'player_count' => count($this->players),
            'max_players' => $this->cachedMaxPlayers,
            'min_players' => $this->cachedMinPlayers,
        ];
    }
}

