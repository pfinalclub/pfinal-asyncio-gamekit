<?php
/**
 * Workerman 环境下的高级游戏示例
 * 展示在 GameServer 中运行游戏，支持完整的定时器功能
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PfinalClub\AsyncioGamekit\GameServer;
use PfinalClub\AsyncioGamekit\Room\Room;
use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\Logger\{LoggerFactory, LogLevel, WorkermanLogHandler};
use PfinalClub\AsyncioGamekit\Persistence\FileAdapter;
use PfinalClub\AsyncioGamekit\Persistence\RoomStateManager;
use function PfinalClub\Asyncio\sleep;

// 配置日志系统 - 使用 Workerman 日志
WorkermanLogHandler::configureWorkerman(__DIR__ . '/../logs/workerman.log');
WorkermanLogHandler::setDaemonize(false); // 非守护进程模式，便于调试

LoggerFactory::configure([
    'min_level' => LogLevel::DEBUG,
    'console' => ['enabled' => true, 'color' => true],
    'file' => [
        'enabled' => true,
        'path' => __DIR__ . '/../logs/game.log',
    ],
]);

/**
 * 完整功能的游戏房间（支持定时器）
 */
class FullFeaturedGameRoom extends Room
{
    private RoomStateManager $stateManager;
    private int $saveInterval = 10; // 10秒保存一次
    private int $gameTime = 0;

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
            'game_duration' => 60,
            'auto_save' => true,
        ];
    }

    protected function onCreate(): mixed
    {
        LoggerFactory::info("Creating game room with full features", [
            'room_id' => $this->id,
        ]);

        // 尝试恢复状态
        if ($this->stateManager->hasRoomState($this->id)) {
            $state = $this->stateManager->getRoomState($this->id);
            LoggerFactory::info("Restored previous game state", [
                'room_id' => $this->id,
            ]);
        }
        
        return null;
    }

    protected function onStart(): mixed
    {
        LoggerFactory::info("Starting game with timer support", [
            'room_id' => $this->id,
            'player_count' => $this->getPlayerCount(),
        ]);

        $this->broadcast('game:start', [
            'message' => '游戏开始！支持完整的定时器功能',
            'duration' => $this->config['game_duration'],
        ]);

        // 启动游戏计时器
        $this->addTimer(1.0, function() {
            $this->gameTime++;
            
            // 每5秒广播一次游戏时间
            if ($this->gameTime % 5 === 0) {
                $this->broadcast('game:time', [
                    'elapsed' => $this->gameTime,
                    'remaining' => $this->config['game_duration'] - $this->gameTime,
                ]);
                
                LoggerFactory::debug("Game time update", [
                    'room_id' => $this->id,
                    'elapsed' => $this->gameTime,
                ]);
            }
        }, true);

        // 启动自动保存
        if ($this->config['auto_save']) {
            $this->addTimer($this->saveInterval, function() {
                LoggerFactory::debug("Auto-saving room state", [
                    'room_id' => $this->id,
                ]);
                $this->stateManager->saveRoomState($this);
            }, true);
        }

        sleep(2);
        
        return null;
    }

    protected function run(): mixed
    {
        $duration = $this->config['game_duration'];
        $startTime = time();

        LoggerFactory::info("Game loop started", ['room_id' => $this->id]);

        while ((time() - $startTime) < $duration) {
            // 模拟游戏事件
            if (rand(1, 15) === 1) {
                $this->triggerRandomEvent();
            }

                sleep(1);
        }

        // 游戏结束
        $this->gameEnd();
        
        sleep(2);
        $this->destroy();
        
        return null;
    }

    private function triggerRandomEvent(): void
    {
        $events = [
            ['type' => 'bonus', 'message' => '获得奖励！', 'points' => 10],
            ['type' => 'challenge', 'message' => '遇到挑战！', 'difficulty' => 3],
            ['type' => 'powerup', 'message' => '获得能量提升！'],
        ];

        $event = $events[array_rand($events)];
        
        LoggerFactory::debug("Random event triggered", [
            'room_id' => $this->id,
            'event' => $event['type'],
        ]);

        $this->broadcast('game:event', $event);
    }

    private function gameEnd(): void
    {
        $scores = [];
        foreach ($this->players as $player) {
            $scores[] = [
                'player_name' => $player->getName(),
                'score' => $player->get('score', rand(50, 150)),
            ];
        }

        usort($scores, fn($a, $b) => $b['score'] - $a['score']);

        LoggerFactory::info("Game ended", [
            'room_id' => $this->id,
            'winner' => $scores[0]['player_name'] ?? 'None',
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
            'game_time' => $this->gameTime,
            'finished_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function onPlayerMessage(Player $player, string $event, mixed $data): mixed
    {
        LoggerFactory::debug("Player message", [
            'room_id' => $this->id,
            'player' => $player->getName(),
            'event' => $event,
        ]);

        switch ($event) {
            case 'action':
                $score = $player->get('score', 0);
                $player->set('score', $score + 10);
                
                $this->broadcast('player:action', [
                    'player' => $player->getName(),
                    'score' => $player->get('score'),
                ]);
                break;
        }
        
        return null;
    }

    protected function onDestroy(): mixed
    {
        LoggerFactory::info("Destroying room", ['room_id' => $this->id]);
        $this->stateManager->deleteRoomState($this->id);
        
        return null;
    }
}

// ==================== 启动 GameServer ====================

echo "=== Workerman 高级游戏服务器示例 ===\n";
echo "监听地址: ws://0.0.0.0:2345\n";
echo "功能: 完整的定时器支持、自动保存、状态持久化\n\n";

$server = new GameServer('0.0.0.0', 2345, [
    'name' => 'AdvancedGameServer',
    'count' => 1, // 单进程便于调试
]);

echo "使用方法:\n";
echo "1. 打开浏览器，访问 examples/client.html\n";
echo "2. 连接到 ws://localhost:2345\n";
echo "3. 设置名称并快速匹配\n";
echo "4. 游戏类名: FullFeaturedGameRoom\n\n";

echo "服务器启动中...\n";

$server->run();

