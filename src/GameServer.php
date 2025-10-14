<?php

namespace PfinalClub\AsyncioGamekit;

use Workerman\Worker;
use Workerman\Connection\TcpConnection;

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

        try {
            $message = json_decode($data, true);
            if (!$message || !isset($message['event'])) {
                return;
            }

            $event = $message['event'];
            $payload = $message['data'] ?? null;

            // 处理系统事件
            $this->handleSystemEvent($player, $event, $payload);

            // 如果玩家在房间中，转发给房间处理
            $room = $this->roomManager->getPlayerRoom($player);
            if ($room) {
                \PfinalClub\Asyncio\create_task(
                    $room->onPlayerMessage($player, $event, $payload)
                );
            }

        } catch (\Exception $e) {
            $player->send('error', ['message' => $e->getMessage()]);
        }
    }

    /**
     * 处理系统事件
     */
    protected function handleSystemEvent(Player $player, string $event, mixed $data): void
    {
        switch ($event) {
            case 'set_name':
                $player->setName($data['name'] ?? 'Anonymous');
                $player->send('name_set', ['name' => $player->getName()]);
                break;

            case 'create_room':
                $roomClass = $data['room_class'] ?? null;
                $config = $data['config'] ?? [];
                if ($roomClass) {
                    $room = $this->roomManager->createRoom($roomClass, null, $config);
                    $this->roomManager->joinRoom($player, $room->getId());
                    $player->send('room_created', $room->toArray());
                }
                break;

            case 'join_room':
                $roomId = $data['room_id'] ?? null;
                if ($roomId) {
                    $success = $this->roomManager->joinRoom($player, $roomId);
                    if ($success) {
                        $room = $this->roomManager->getRoom($roomId);
                        $player->send('room_joined', $room->toArray());
                    } else {
                        $player->send('error', ['message' => 'Failed to join room']);
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
                if ($roomClass) {
                    $room = $this->roomManager->quickMatch($player, $roomClass, $config);
                    $player->send('matched', $room->toArray());
                }
                break;

            case 'get_rooms':
                $rooms = array_map(fn($r) => $r->toArray(), $this->roomManager->getRooms());
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
}

