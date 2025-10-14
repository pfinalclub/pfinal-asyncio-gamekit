<?php
/**
 * 简单游戏示例：倒计时游戏
 * 展示基本的 Room 使用方法
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PfinalClub\AsyncioGamekit\Room;
use PfinalClub\AsyncioGamekit\Player;
use function PfinalClub\Asyncio\{run, sleep};

/**
 * 倒计时游戏房间
 */
class CountdownRoom extends Room
{
    protected function getDefaultConfig(): array
    {
        return [
            'max_players' => 4,
            'min_players' => 1,
            'countdown_seconds' => 10,
        ];
    }

    /**
     * 房间创建时
     */
    protected function onCreate(): Generator
    {
        echo "房间 {$this->id} 创建成功\n";
        $this->broadcast('room:created', [
            'message' => '欢迎来到倒计时游戏！',
            'countdown' => $this->config['countdown_seconds']
        ]);
        yield;
    }

    /**
     * 游戏开始时
     */
    protected function onStart(): Generator
    {
        echo "游戏开始！\n";
        $this->broadcast('game:start', [
            'message' => '游戏即将开始！',
            'players' => array_map(fn($p) => $p->toArray(), $this->players)
        ]);
        yield sleep(1);
    }

    /**
     * 游戏主逻辑
     */
    protected function run(): Generator
    {
        $countdown = $this->config['countdown_seconds'];
        
        // 倒计时
        for ($i = $countdown; $i > 0; $i--) {
            echo "倒计时: {$i}\n";
            $this->broadcast('game:countdown', ['seconds' => $i]);
            yield sleep(1);
        }

        // 游戏结束
        echo "游戏结束！\n";
        $this->broadcast('game:end', [
            'message' => '倒计时结束！游戏结束！',
            'winner' => $this->determineWinner()
        ]);

        yield sleep(2);
        yield from $this->destroy();
    }

    /**
     * 确定获胜者
     */
    private function determineWinner(): ?array
    {
        // 随机选择一个获胜者
        if (empty($this->players)) {
            return null;
        }
        
        $players = array_values($this->players);
        $winner = $players[array_rand($players)];
        
        return $winner->toArray();
    }

    /**
     * 玩家加入时
     */
    protected function onPlayerJoin(Player $player): void
    {
        echo "玩家 {$player->getName()} 加入房间\n";
    }

    /**
     * 玩家离开时
     */
    protected function onPlayerLeave(Player $player): void
    {
        echo "玩家 {$player->getName()} 离开房间\n";
    }

    /**
     * 处理玩家消息
     */
    public function onPlayerMessage(Player $player, string $event, mixed $data): Generator
    {
        if ($event === 'ready') {
            $player->setReady(true);
            $this->broadcast('player:ready', [
                'player_id' => $player->getId(),
                'player_name' => $player->getName()
            ]);
        }
        yield;
    }

    /**
     * 房间销毁时
     */
    protected function onDestroy(): Generator
    {
        echo "房间 {$this->id} 已销毁\n";
        yield;
    }
}

// ==================== 运行示例 ====================

function main(): Generator
{
    echo "=== 简单倒计时游戏示例 ===\n\n";

    // 创建房间
    $room = new CountdownRoom('room_001', [
        'countdown_seconds' => 5,
        'min_players' => 1
    ]);

    // 创建玩家（模拟）
    $player1 = new Player('p1', null, 'Alice');
    $player2 = new Player('p2', null, 'Bob');

    // 玩家加入房间
    $room->addPlayer($player1);
    $room->addPlayer($player2);

    echo "\n当前房间状态: {$room->getStatus()}\n";
    echo "当前玩家数: {$room->getPlayerCount()}\n\n";

    // 启动游戏
    yield from $room->start();

    echo "\n游戏已结束\n";
}

// 运行
run(main());

