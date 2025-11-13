<?php
/**
 * WebSocket 游戏服务器示例
 * 展示如何使用 GameServer 搭建完整的游戏服务器
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PfinalClub\AsyncioGamekit\GameServer;
use PfinalClub\AsyncioGamekit\Room;
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

        // 添加定时器更新剩余时间
        $this->addTimer(5, function () use (&$startTime, $timeLimit, &$gameEnded) {
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
        while (!$gameEnded && (time() - $startTime) < $timeLimit) {
            sleep(1);
            
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

echo "=== WebSocket 游戏服务器 ===\n";
echo "监听地址: ws://0.0.0.0:2345\n";
echo "支持游戏: GuessNumberRoom\n\n";

$server = new GameServer('0.0.0.0', 2345, [
    'name' => 'GuessNumberGameServer',
    'count' => 1, // 单进程用于测试
]);

echo "服务器启动中...\n";
echo "\n使用方法:\n";
echo "1. 连接 WebSocket: ws://localhost:2345\n";
echo "2. 设置名称: {\"event\": \"set_name\", \"data\": {\"name\": \"YourName\"}}\n";
echo "3. 快速匹配: {\"event\": \"quick_match\", \"data\": {\"room_class\": \"GuessNumberRoom\"}}\n";
echo "4. 猜数字: {\"event\": \"guess\", \"data\": {\"number\": 50}}\n";
echo "5. 查看房间: {\"event\": \"get_rooms\"}\n\n";

$server->run();

