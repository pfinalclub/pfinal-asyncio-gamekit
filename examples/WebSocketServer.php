<?php
/**
 * WebSocket 游戏服务器示例
 * 展示如何使用 GameServer 搭建完整的游戏服务器
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PfinalClub\AsyncioGamekit\GameServer;
use PfinalClub\AsyncioGamekit\Room\Room;
use PfinalClub\AsyncioGamekit\Player;
use function PfinalClub\Asyncio\sleep;

/**
 * 猜数字游戏房间
 */
class GuessNumberRoom extends Room
{
    private int $targetNumber = 0;
    private int $minNumber = 1;
    private int $maxNumber = 100;

    protected function getDefaultConfig(): array
    {
        return [
            'max_players' => 5,
            'min_players' => 2,
            'time_limit' => 60, // 60秒时间限制
        ];
    }

    protected function onStart(): mixed
    {
        // 生成目标数字
        $this->targetNumber = rand($this->minNumber, $this->maxNumber);
        echo "目标数字已生成: {$this->targetNumber}\n";
        
        $this->broadcast('game:start', [
            'message' => "猜数字游戏开始！范围: {$this->minNumber} - {$this->maxNumber}",
            'min' => $this->minNumber,
            'max' => $this->maxNumber,
            'time_limit' => $this->config['time_limit']
        ]);
        
        sleep(1);
        return null;
    }

    protected function run(): mixed
    {
        $timeLimit = $this->config['time_limit'];
        $startTime = time();
        $gameEnded = false;

        // 尝试添加定时器更新剩余时间（如果环境支持）
        // 注意：在 Workerman 环境下，定时器可能不可用，所以使用轮询方式
        $timerId = $this->addTimer(5, function () use (&$startTime, $timeLimit, &$gameEnded) {
            if ($gameEnded) {
                return;
            }
            
            $elapsed = time() - $startTime;
            $remaining = $timeLimit - $elapsed;
            
            if ($remaining > 0) {
                $this->broadcast('game:time_update', ['remaining' => $remaining]);
            }
        }, true);

        // 等待游戏结束或超时
        $lastBroadcastTime = 0;
        while (!$gameEnded && (time() - $startTime) < $timeLimit) {
            sleep(1);
            
            // 如果定时器不可用，手动每5秒广播一次时间更新
            if ($timerId === 0 && (time() - $lastBroadcastTime) >= 5) {
                $elapsed = time() - $startTime;
                $remaining = $timeLimit - $elapsed;
                
                if ($remaining > 0) {
                    $this->broadcast('game:time_update', ['remaining' => $remaining]);
                    $lastBroadcastTime = time();
                }
            }
            
            if ($this->get('game_ended', false)) {
                $gameEnded = true;
            }
        }

        if (!$gameEnded) {
            // 超时
            $this->broadcast('game:timeout', [
                'message' => '时间到！游戏结束！',
                'answer' => $this->targetNumber
            ]);
        }

        sleep(3);
        $this->destroy();
        return null;
    }

    public function onPlayerMessage(Player $player, string $event, mixed $data): mixed
    {
        if ($event === 'guess' && $this->status === 'running') {
            $guess = (int)($data['number'] ?? 0);
            
            echo "{$player->getName()} 猜测: {$guess}\n";
            
            if ($guess === $this->targetNumber) {
                // 猜对了！
                $this->broadcast('game:won', [
                    'winner' => $player->toArray(),
                    'number' => $this->targetNumber,
                    'message' => "{$player->getName()} 猜对了！答案是 {$this->targetNumber}"
                ]);
                
                // 记录获胜者
                $player->set('won', true);
                $this->set('game_ended', true);
                
            } else {
                // 给出提示
                $hint = $guess < $this->targetNumber ? '太小了！' : '太大了！';
                
                $player->send('game:hint', [
                    'guess' => $guess,
                    'hint' => $hint
                ]);
                
                // 广播猜测记录
                $this->broadcast('game:guess_record', [
                    'player_name' => $player->getName(),
                    'guess' => $guess,
                    'hint' => $hint
                ], $player);
            }
        }
        return null;
    }

    protected function onPlayerJoin(Player $player): void
    {
        echo "玩家 {$player->getName()} 加入猜数字游戏\n";
    }

    protected function onPlayerLeave(Player $player): void
    {
        echo "玩家 {$player->getName()} 离开猜数字游戏\n";
        
        // 如果房间正在运行且玩家太少，结束游戏
        if ($this->isRunning && $this->getPlayerCount() < $this->config['min_players']) {
            $this->set('game_ended', true);
        }
    }
}

// ==================== 启动服务器 ====================

// 检查命令行参数
$command = $argv[1] ?? 'start';
$mode = $argv[2] ?? '';

// 显示使用说明
if ($command === 'help' || $command === '-h' || $command === '--help') {
    echo "=== WebSocket 游戏服务器 ===\n";
    echo "监听地址: ws://0.0.0.0:2345\n";
    echo "支持游戏: GuessNumberRoom\n\n";
    echo "使用方法:\n";
    echo "1. 连接 WebSocket: ws://localhost:2345\n";
    echo "2. 设置名称: {\"event\": \"set_name\", \"data\": {\"name\": \"YourName\"}}\n";
    echo "3. 快速匹配: {\"event\": \"quick_match\", \"data\": {\"room_class\": \"GuessNumberRoom\"}}\n";
    echo "4. 猜数字: {\"event\": \"guess\", \"data\": {\"number\": 50}}\n";
    echo "5. 查看房间: {\"event\": \"get_rooms\"}\n\n";
    echo "命令:\n";
    echo "  start    启动服务器（调试模式）\n";
    echo "  start -d 启动服务器（守护进程模式）\n";
    echo "  stop     停止服务器\n";
    echo "  restart  重启服务器\n";
    echo "  reload   重载代码\n";
    echo "  status   查看状态\n";
    exit(0);
}

// 创建服务器实例
$server = new GameServer('0.0.0.0', 2345, [
    'name' => 'GuessNumberGameServer',
    'count' => ($mode === '-d') ? 4 : 1, // 守护进程模式使用多进程
]);

// 设置 Workerman 运行模式
if ($mode === '-d') {
    \Workerman\Worker::$daemonize = true;
}

// 显示启动信息
if ($command === 'start') {
    echo "=== WebSocket 游戏服务器 ===\n";
    echo "监听地址: ws://0.0.0.0:2345\n";
    echo "支持游戏: GuessNumberRoom\n";
    echo "运行模式: " . ($mode === '-d' ? '守护进程' : '调试模式') . "\n\n";
    echo "使用方法:\n";
    echo "1. 连接 WebSocket: ws://localhost:2345\n";
    echo "2. 设置名称: {\"event\": \"set_name\", \"data\": {\"name\": \"YourName\"}}\n";
    echo "3. 快速匹配: {\"event\": \"quick_match\", \"data\": {\"room_class\": \"GuessNumberRoom\"}}\n";
    echo "4. 猜数字: {\"event\": \"guess\", \"data\": {\"number\": 50}}\n";
    echo "5. 查看房间: {\"event\": \"get_rooms\"}\n\n";
    echo "服务器启动中...\n\n";
}

// 运行服务器（Workerman 会自动处理 start/stop/restart/reload/status 命令）
$server->run();

