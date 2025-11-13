<?php

namespace PfinalClub\AsyncioGamekit;

use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use PfinalClub\AsyncioGamekit\Exceptions\{GameException, RoomException, ServerException};
use PfinalClub\AsyncioGamekit\Logger\LoggerFactory;
use PfinalClub\AsyncioGamekit\RateLimit\{RateLimiterInterface, TokenBucketLimiter};

/**
 * GameServer 游戏服务器
 * 基于 Workerman 的 WebSocket 游戏服务器
 */
class GameServer
{
    /** @var Worker */
    private Worker $worker;
    
    /** @var RoomManager */
    private RoomManager $roomManager;
    
    /** @var array<string, Player> 连接ID到玩家的映射 */
    private array $connections = [];
    
    /** @var array 服务器配置 */
    private array $config;
    
    /** @var array 允许的房间类白名单 */
    protected array $allowedRoomClasses = [];
    
    /** @var RateLimiterInterface 限流器 */
    protected RateLimiterInterface $rateLimiter;

    /**
     * @param string $host 监听地址
     * @param int $port 监听端口
     * @param array $config 配置
     */
    public function __construct(string $host = '0.0.0.0', int $port = 2345, array $config = [])
    {
        $this->config = array_merge([
            'name' => 'GameServer',
            'count' => 4,
            'protocol' => 'websocket',
        ], $config);
        
        // 设置允许的房间类
        $this->allowedRoomClasses = $config['allowed_room_classes'] ?? [];
        
        // 初始化限流器
        $this->rateLimiter = $config['rate_limiter'] ?? new TokenBucketLimiter();

        $protocol = $this->config['protocol'];
        $this->worker = new Worker("{$protocol}://{$host}:{$port}");
        $this->worker->name = $this->config['name'];
        $this->worker->count = $this->config['count'];
        
        $this->roomManager = new RoomManager();
        
        $this->setupCallbacks();
    }

    /**
     * 设置回调函数
     */
    private function setupCallbacks(): void
    {
        $this->worker->onConnect = function (TcpConnection $connection) {
            echo "New connection: {$connection->id}\n";
            $this->onConnect($connection);
        };

        $this->worker->onMessage = function (TcpConnection $connection, $data) {
            $this->onMessage($connection, $data);
        };

        $this->worker->onClose = function (TcpConnection $connection) {
            echo "Connection closed: {$connection->id}\n";
            $this->onClose($connection);
        };

        $this->worker->onWorkerStart = function () {
            echo "{$this->config['name']} started\n";
            $this->onWorkerStart();
        };
    }

    /**
     * 连接建立时
     */
    protected function onConnect(TcpConnection $connection): void
    {
        // 创建玩家对象
        $playerId = uniqid('player_', true);
        $player = new Player($playerId, $connection);
        
        $this->connections[$connection->id] = $player;
        
        LoggerFactory::info("New player connected: {player_id}", [
            'player_id' => $playerId,
            'connection_id' => $connection->id,
        ]);
        
        // 发送欢迎消息
        $player->send('connected', [
            'player_id' => $playerId,
            'server_time' => microtime(true)
        ]);
    }

    /**
     * 收到消息时
     */
    protected function onMessage(TcpConnection $connection, string $data): void
    {
        $player = $this->connections[$connection->id] ?? null;
        if (!$player) {
            return;
        }

        // 限流检查：每个玩家每秒最多 20 条消息（容量20，速率20/秒）
        $playerId = $player->getId();
        if (!$this->rateLimiter->allow($playerId, 20, 20)) {
            LoggerFactory::warning("Rate limit exceeded for player", [
                'player_id' => $playerId,
                'player_name' => $player->getName(),
            ]);
            
            $player->send('error', [
                'message' => 'Too many requests. Please slow down.',
                'code' => 'RATE_LIMIT_EXCEEDED'
            ]);
            return;
        }

        try {
            $message = json_decode($data, true);
            if (!$message || !isset($message['event'])) {
                throw ServerException::invalidMessageFormat($data);
            }

            $event = $message['event'];
            $payload = $message['data'] ?? null;

            // 处理系统事件
            $this->handleSystemEvent($player, $event, $payload);

            // 如果玩家在房间中，转发给房间处理
            $room = $this->roomManager->getPlayerRoom($player);
            if ($room) {
                \PfinalClub\Asyncio\create_task(
                    fn() => $room->onPlayerMessage($player, $event, $payload)
                );
            }

        } catch (GameException $e) {
            // 游戏框架异常
            $player->send('error', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'context' => $e->getContext()
            ]);
            $this->logError($e);
        } catch (\Throwable $e) {
            // 其他异常
            $player->send('error', ['message' => 'Internal server error']);
            $this->logError($e);
        }
    }

    /**
     * 处理系统事件
     * 
     * @throws RoomException
     */
    protected function handleSystemEvent(Player $player, string $event, mixed $data): void
    {
        switch ($event) {
            case 'set_name':
                $name = $data['name'] ?? 'Anonymous';
                // 限制名称长度
                $name = mb_substr($name, 0, 32);
                $player->setName($name);
                $player->send('name_set', ['name' => $player->getName()]);
                break;

            case 'create_room':
                $roomClass = $data['room_class'] ?? null;
                $config = $data['config'] ?? [];
                
                // 验证房间类
                if (!$this->isRoomClassAllowed($roomClass)) {
                    $player->send('error', [
                        'message' => 'Invalid or not allowed room class',
                        'code' => 'INVALID_ROOM_CLASS'
                    ]);
                    return;
                }
                
                // 清理和验证配置
                $config = $this->sanitizeRoomConfig($config);
                
                try {
                    $room = $this->roomManager->createRoom($roomClass, null, $config);
                    $this->roomManager->joinRoom($player, $room->getId());
                    $player->send('room_created', $room->toArray());
                } catch (\Throwable $e) {
                    $player->send('error', [
                        'message' => 'Failed to create room',
                        'code' => 'CREATE_ROOM_FAILED'
                    ]);
                    $this->logError($e);
                }
                break;

            case 'join_room':
                $roomId = $data['room_id'] ?? null;
                if ($roomId) {
                    try {
                        $this->roomManager->joinRoom($player, $roomId);
                        $room = $this->roomManager->getRoom($roomId);
                        $player->send('room_joined', $room->toArray());
                    } catch (RoomException $e) {
                        $player->send('error', [
                            'message' => $e->getMessage(),
                            'code' => $e->getCode()
                        ]);
                    }
                }
                break;

            case 'leave_room':
                $this->roomManager->leaveRoom($player);
                $player->send('room_left', []);
                break;

            case 'quick_match':
                $roomClass = $data['room_class'] ?? null;
                $config = $data['config'] ?? [];
                
                // 验证房间类
                if (!$this->isRoomClassAllowed($roomClass)) {
                    $player->send('error', [
                        'message' => 'Invalid or not allowed room class',
                        'code' => 'INVALID_ROOM_CLASS'
                    ]);
                    return;
                }
                
                // 清理和验证配置
                $config = $this->sanitizeRoomConfig($config);
                
                try {
                    $room = $this->roomManager->quickMatch($player, $roomClass, $config);
                    $player->send('matched', $room->toArray());
                } catch (\Throwable $e) {
                    $player->send('error', [
                        'message' => 'Quick match failed',
                        'code' => 'QUICK_MATCH_FAILED'
                    ]);
                    $this->logError($e);
                }
                break;

            case 'get_rooms':
                // 使用轻量级版本提高性能
                $rooms = array_map(fn($r) => $r->toArrayLight(), $this->roomManager->getRooms());
                $player->send('rooms_list', ['rooms' => array_values($rooms)]);
                break;

            case 'get_stats':
                $player->send('stats', $this->roomManager->getStats());
                break;
        }
    }

    /**
     * 连接关闭时
     */
    protected function onClose(TcpConnection $connection): void
    {
        $player = $this->connections[$connection->id] ?? null;
        if (!$player) {
            return;
        }

        LoggerFactory::info("Player disconnected: {player_name}", [
            'player_id' => $player->getId(),
            'player_name' => $player->getName(),
        ]);

        // 玩家离开房间
        $this->roomManager->leaveRoom($player);
        
        unset($this->connections[$connection->id]);
    }

    /**
     * Worker 启动时
     */
    protected function onWorkerStart(): void
    {
        // 子类可重写
    }

    /**
     * 获取房间管理器
     */
    public function getRoomManager(): RoomManager
    {
        return $this->roomManager;
    }

    /**
     * 运行服务器
     */
    public function run(): void
    {
        Worker::runAll();
    }

    /**
     * 记录错误
     */
    protected function logError(\Throwable $e): void
    {
        $context = [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        if ($e instanceof GameException) {
            $context = array_merge($context, $e->getContext());
        }

        LoggerFactory::error("Server error: {message}", $context);
    }
    
    /**
     * 检查房间类是否允许
     * 
     * @param string|null $roomClass 房间类名
     * @return bool
     */
    protected function isRoomClassAllowed(?string $roomClass): bool
    {
        if (!$roomClass) {
            return false;
        }
        
        // 如果没有配置白名单，检查类是否存在且继承自 Room
        if (empty($this->allowedRoomClasses)) {
            return class_exists($roomClass) && is_subclass_of($roomClass, Room::class);
        }
        
        // 使用白名单验证
        return in_array($roomClass, $this->allowedRoomClasses, true);
    }
    
    /**
     * 清理和验证房间配置
     * 
     * @param array $config 原始配置
     * @return array 清理后的配置
     */
    protected function sanitizeRoomConfig(array $config): array
    {
        $allowed = ['max_players', 'min_players', 'auto_start'];
        $sanitized = [];
        
        foreach ($allowed as $key) {
            if (isset($config[$key])) {
                $sanitized[$key] = $config[$key];
            }
        }
        
        // 限制玩家数量范围
        if (isset($sanitized['max_players'])) {
            $sanitized['max_players'] = max(2, min(100, (int)$sanitized['max_players']));
        }
        if (isset($sanitized['min_players'])) {
            $sanitized['min_players'] = max(1, min(100, (int)$sanitized['min_players']));
        }
        if (isset($sanitized['auto_start'])) {
            $sanitized['auto_start'] = (bool)$sanitized['auto_start'];
        }
        
        return $sanitized;
    }
}

