<?php
/**
 * 高级游戏示例
 * 展示日志、异常处理、持久化等新特性
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PfinalClub\AsyncioGamekit\Room\Room;
use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\Exceptions\RoomException;
use PfinalClub\AsyncioGamekit\Logger\LoggerFactory;
use PfinalClub\AsyncioGamekit\Logger\LogLevel;
use PfinalClub\AsyncioGamekit\Persistence\FileAdapter;
use PfinalClub\AsyncioGamekit\Persistence\RoomStateManager;
use function PfinalClub\Asyncio\{run, sleep};

// 配置日志系统
LoggerFactory::configure([
    'min_level' => LogLevel::DEBUG,
    'console' => [
        'enabled' => true,
        'color' => true,
    ],
    'file' => [
        'enabled' => true,
        'path' => __DIR__ . '/../logs/game.log',
        'max_size' => 5 * 1024 * 1024, // 5MB
        'max_files' => 3,
    ],
]);

/**
 * 高级游戏房间
 */
class AdvancedGameRoom extends Room
{
    private RoomStateManager $stateManager;
    private int $saveInterval = 30; // 30秒保存一次

    public function __construct(string $id, array $config = [])
    {
        parent::__construct($id, $config);
        
        // 初始化状态管理器
        $adapter = new FileAdapter(__DIR__ . '/../storage');
        $this->stateManager = new RoomStateManager($adapter);
    }

    protected function getDefaultConfig(): array
    {
        return [
            'max_players' => 4,
            'min_players' => 2,
            'game_duration' => 120, // 游戏时长（秒）
            'auto_save' => true,    // 自动保存
        ];
    }

    /**
     * 房间创建
     */
    protected function onCreate(): mixed
    {
        LoggerFactory::info("Creating advanced game room: {room_id}", [
            'room_id' => $this->id,
            'config' => $this->config,
        ]);

        // 尝试恢复之前的状态
        $savedState = $this->stateManager->getRoomState($this->id);
        if ($savedState) {
            LoggerFactory::info("Restored room state from persistence", [
                'room_id' => $this->id,
            ]);
            $this->restoreState($savedState);
        }

        $this->broadcast('room:created', [
            'message' => '高级游戏房间已创建！',
            'features' => ['日志记录', '状态持久化', '异常处理'],
        ]);
        
        return null;
    }

    /**
     * 游戏开始
     */
    protected function onStart(): mixed
    {
        LoggerFactory::info("Starting advanced game in room {room_id}", [
            'room_id' => $this->id,
            'player_count' => $this->getPlayerCount(),
        ]);

        $this->broadcast('game:start', [
            'message' => '游戏开始！',
            'duration' => $this->config['game_duration'],
        ]);

        // 注意：定时器功能需要在 Workerman Worker 环境下才能使用
        // 在纯 asyncio 环境下运行时，定时器不可用
        // 如需使用定时器，请在 GameServer 中运行此游戏

        sleep(2);
        
        return null;
    }

    /**
     * 游戏主逻辑
     */
    protected function run(): mixed
    {
        $duration = $this->config['game_duration'];
        $startTime = time();

        LoggerFactory::info("Game loop started", ['room_id' => $this->id]);

        try {
            while ((time() - $startTime) < $duration) {
                $elapsed = time() - $startTime;
                $remaining = $duration - $elapsed;

                // 每10秒广播一次
                if ($elapsed % 10 === 0) {
                    $this->broadcast('game:update', [
                        'elapsed' => $elapsed,
                        'remaining' => $remaining,
                        'players' => array_map(fn($p) => $p->toArray(), $this->players),
                    ]);
                }

                sleep(1);

                // 模拟随机事件
                if (rand(1, 20) === 1) {
                    $this->triggerRandomEvent();
                }
            }

            // 游戏结束
            LoggerFactory::info("Game finished normally", ['room_id' => $this->id]);
            $this->gameEnd();

        } catch (\Throwable $e) {
            LoggerFactory::error("Game error in room {room_id}: {message}", [
                'room_id' => $this->id,
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            throw $e;
        }

        sleep(3);
        $this->destroy();
        
        return null;
    }

    /**
     * 触发随机事件
     */
    private function triggerRandomEvent(): void
    {
        $events = [
            ['type' => 'bonus', 'message' => '获得奖励点数！', 'points' => 10],
            ['type' => 'challenge', 'message' => '遇到挑战！', 'difficulty' => 5],
            ['type' => 'item', 'message' => '发现道具！', 'item' => 'sword'],
        ];

        $event = $events[array_rand($events)];
        
        LoggerFactory::debug("Random event triggered", [
            'room_id' => $this->id,
            'event' => $event,
        ]);

        $this->broadcast('game:event', $event);
    }

    /**
     * 游戏结束
     */
    private function gameEnd(): void
    {
        // 统计分数
        $scores = [];
        foreach ($this->players as $player) {
            $scores[] = [
                'player_id' => $player->getId(),
                'player_name' => $player->getName(),
                'score' => $player->get('score', 0),
            ];
        }

        usort($scores, fn($a, $b) => $b['score'] - $a['score']);

        LoggerFactory::info("Game ended with results", [
            'room_id' => $this->id,
            'winner' => $scores[0] ?? null,
        ]);

        $this->broadcast('game:end', [
            'message' => '游戏结束！',
            'scores' => $scores,
            'winner' => $scores[0] ?? null,
        ]);

        // 保存游戏统计
        $this->stateManager->saveGameStats('game_' . $this->id, [
            'room_id' => $this->id,
            'scores' => $scores,
            'duration' => $this->config['game_duration'],
            'finished_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 启动自动保存（仅在 Workerman Worker 环境下可用）
     */
    private function startAutoSave(): void
    {
        try {
            $timerId = $this->addTimer($this->saveInterval, function() {
                LoggerFactory::debug("Auto-saving room state", ['room_id' => $this->id]);
                $this->stateManager->saveRoomState($this);
            }, true);

            if ($timerId === null) {
                LoggerFactory::warning("Auto-save timer not available (not in Worker environment)", [
                    'room_id' => $this->id
                ]);
            }
        } catch (\TypeError $e) {
            // 在非 Worker 环境下，定时器功能不可用
            LoggerFactory::info("Timer feature not available in pure asyncio environment", [
                'room_id' => $this->id
            ]);
        }
    }

    /**
     * 恢复状态
     */
    private function restoreState(array $state): void
    {
        $this->data = $state['data'] ?? [];
        LoggerFactory::debug("State restored", [
            'room_id' => $this->id,
            'data_keys' => array_keys($this->data),
        ]);
    }

    /**
     * 处理玩家消息
     */
    public function onPlayerMessage(Player $player, string $event, mixed $data): mixed
    {
        LoggerFactory::debug("Player message received", [
            'room_id' => $this->id,
            'player_id' => $player->getId(),
            'event' => $event,
        ]);

        try {
            switch ($event) {
                case 'action':
                    $this->handlePlayerAction($player, $data);
                    break;

                case 'get_score':
                    $player->send('score', [
                        'score' => $player->get('score', 0),
                    ]);
                    break;
            }
        } catch (RoomException $e) {
            LoggerFactory::warning("Player action failed", [
                'room_id' => $this->id,
                'player_id' => $player->getId(),
                'error' => $e->getMessage(),
            ]);
            
            $player->send('error', $e->toArray());
        }
        
        return null;
    }

    /**
     * 处理玩家动作
     */
    private function handlePlayerAction(Player $player, mixed $data): void
    {
        $action = $data['action'] ?? '';
        
        LoggerFactory::info("Player action: {action}", [
            'room_id' => $this->id,
            'player_id' => $player->getId(),
            'action' => $action,
        ]);

        // 增加分数
        $currentScore = $player->get('score', 0);
        $player->set('score', $currentScore + 5);

        $this->broadcast('player:action', [
            'player_id' => $player->getId(),
            'player_name' => $player->getName(),
            'action' => $action,
            'score' => $player->get('score'),
        ]);
    }

    /**
     * 房间销毁
     */
    protected function onDestroy(): mixed
    {
        LoggerFactory::info("Destroying room {room_id}", ['room_id' => $this->id]);
        
        // 清理持久化数据
        $this->stateManager->deleteRoomState($this->id);
        
        return null;
    }
}

// ==================== 运行示例 ====================

function main(): mixed
{
    echo "=== 高级游戏示例 ===\n";
    echo "展示日志、异常处理、持久化等特性\n\n";

    try {
        // 创建房间
        $room = new AdvancedGameRoom('advanced_room_001', [
            'game_duration' => 30,
            'min_players' => 1,
            'auto_save' => true,
        ]);

        // 创建玩家
        $players = [
            new Player('p1', null, 'Alice'),
            new Player('p2', null, 'Bob'),
        ];

        // 玩家加入
        foreach ($players as $player) {
            try {
                $room->addPlayer($player);
                $player->set('score', 0);
            } catch (RoomException $e) {
                LoggerFactory::error("Failed to add player: {message}", [
                    'message' => $e->getMessage(),
                    'context' => $e->getContext(),
                ]);
            }
        }

        echo "\n当前玩家数: {$room->getPlayerCount()}\n";
        echo "房间状态: {$room->getStatus()}\n\n";

        // 启动游戏
        $room->start();

        echo "\n游戏已结束！\n";
        echo "查看日志: logs/game.log\n";

    } catch (\Throwable $e) {
        LoggerFactory::critical("Critical error: {message}", [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
        
        echo "\n严重错误: {$e->getMessage()}\n";
    }
}

run(main(...));

