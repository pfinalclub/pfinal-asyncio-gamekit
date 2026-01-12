<?php
/**
 * 卡牌游戏示例
 * 展示更复杂的游戏逻辑和定时任务
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PfinalClub\AsyncioGamekit\Room\Room;
use PfinalClub\AsyncioGamekit\Player;
use function PfinalClub\Asyncio\{run, sleep, create_task};

/**
 * 简单的抽牌游戏房间
 */
class CardGameRoom extends Room
{
    private array $deck = [];
    private int $currentPlayerIndex = 0;
    private int $roundNumber = 0;

    protected function getDefaultConfig(): array
    {
        return [
            'max_players' => 4,
            'min_players' => 2,
            'rounds' => 3,
            'turn_timeout' => 10,
        ];
    }

    /**
     * 初始化牌堆
     */
    private function initDeck(): void
    {
        $suits = ['♠', '♥', '♣', '♦'];
        $values = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
        
        $this->deck = [];
        foreach ($suits as $suit) {
            foreach ($values as $value) {
                $this->deck[] = ['suit' => $suit, 'value' => $value];
            }
        }
        
        shuffle($this->deck);
    }

    /**
     * 发牌
     */
    private function dealCards(): void
    {
        foreach ($this->players as $player) {
            $cards = [];
            for ($i = 0; $i < 3; $i++) {
                if (!empty($this->deck)) {
                    $cards[] = array_pop($this->deck);
                }
            }
            $player->set('cards', $cards);
            $player->set('score', 0);
            
            // 只发送给玩家自己的牌
            $player->send('game:cards_dealt', ['cards' => $cards]);
        }
    }

    /**
     * 游戏开始
     */
    protected function onStart(): mixed
    {
        echo "卡牌游戏开始！\n";
        $this->initDeck();
        $this->dealCards();
        
        $this->broadcast('game:start', [
            'message' => '游戏开始！每人获得3张牌',
            'rounds' => $this->config['rounds']
        ]);
        
        sleep(2);
        
        return null;
    }

    /**
     * 游戏主逻辑
     */
    protected function run(): mixed
    {
        $totalRounds = $this->config['rounds'];
        
        for ($round = 1; $round <= $totalRounds; $round++) {
            $this->roundNumber = $round;
            echo "\n=== 第 {$round} 回合 ===\n";
            
            $this->broadcast('game:round_start', [
                'round' => $round,
                'total_rounds' => $totalRounds
            ]);
            
            $this->playRound();
            
            // 回合间隔
            sleep(2);
        }
        
        // 游戏结束，统计得分
        $this->gameEnd();
        
        return null;
    }

    /**
     * 进行一个回合
     */
    private function playRound(): mixed
    {
        $playerList = array_values($this->players);
        
        foreach ($playerList as $index => $player) {
            $this->currentPlayerIndex = $index;
            
            echo "轮到 {$player->getName()} 出牌\n";
            
            $this->broadcast('game:turn_start', [
                'player_id' => $player->getId(),
                'player_name' => $player->getName(),
                'round' => $this->roundNumber
            ]);
            
            // 等待玩家出牌或超时
            $player->set('action_taken', false);
            
            // 设置超时定时器
            $timeout = $this->config['turn_timeout'];
            $elapsed = 0;
            $step = 0.1;
            
            // 限制最大循环次数，防止无限循环
            $maxIterations = (int)($timeout / $step) + 10;
            $iterations = 0;
            
            while (!$player->get('action_taken', false) && $elapsed < $timeout && $iterations < $maxIterations) {
                sleep($step);
                $elapsed += $step;
                $iterations++;
            }
            
            // 超时或未完成则自动出牌
            if (!$player->get('action_taken', false)) {
                echo "{$player->getName()} 超时，自动出牌\n";
                $this->autoPlay($player);
            }
            
            sleep(1);
        }
        
        return null;
    }

    /**
     * 自动出牌
     */
    private function autoPlay(Player $player): void
    {
        $cards = $player->get('cards', []);
        if (!empty($cards)) {
            $card = array_pop($cards);
            $player->set('cards', $cards);
            
            $this->broadcast('game:card_played', [
                'player_id' => $player->getId(),
                'player_name' => $player->getName(),
                'card' => $card,
                'auto' => true
            ]);
            
            // 简单计分：A=1, J=11, Q=12, K=13
            $score = $this->calculateCardScore($card);
            $currentScore = $player->get('score', 0);
            $player->set('score', $currentScore + $score);
        }
        
        $player->set('action_taken', true);
    }

    /**
     * 计算牌的分数
     */
    private function calculateCardScore(array $card): int
    {
        $valueMap = [
            'A' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5,
            '6' => 6, '7' => 7, '8' => 8, '9' => 9, '10' => 10,
            'J' => 11, 'Q' => 12, 'K' => 13
        ];
        return $valueMap[$card['value']] ?? 0;
    }

    /**
     * 游戏结束
     */
    private function gameEnd(): mixed
    {
        echo "\n游戏结束！统计得分...\n";
        
        // 统计得分
        $scores = [];
        foreach ($this->players as $player) {
            $scores[] = [
                'player_id' => $player->getId(),
                'player_name' => $player->getName(),
                'score' => $player->get('score', 0)
            ];
        }
        
        // 按得分排序
        usort($scores, fn($a, $b) => $b['score'] - $a['score']);
        
        $this->broadcast('game:end', [
            'message' => '游戏结束！',
            'scores' => $scores,
            'winner' => $scores[0] ?? null
        ]);
        
        echo "获胜者: {$scores[0]['player_name']} (得分: {$scores[0]['score']})\n";
        
        sleep(3);
        $this->destroy();
        
        return null;
    }

    /**
     * 处理玩家消息
     */
    public function onPlayerMessage(Player $player, string $event, mixed $data): mixed
    {
        if ($event === 'play_card' && !$player->get('action_taken', false)) {
            $cardIndex = $data['card_index'] ?? 0;
            $cards = $player->get('cards', []);
            
            if (isset($cards[$cardIndex])) {
                $card = $cards[$cardIndex];
                array_splice($cards, $cardIndex, 1);
                $player->set('cards', $cards);
                
                $this->broadcast('game:card_played', [
                    'player_id' => $player->getId(),
                    'player_name' => $player->getName(),
                    'card' => $card
                ]);
                
                // 计分
                $score = $this->calculateCardScore($card);
                $currentScore = $player->get('score', 0);
                $player->set('score', $currentScore + $score);
                
                $player->set('action_taken', true);
            }
        }
        
        return null;
    }

    protected function onPlayerJoin(Player $player): void
    {
        echo "玩家 {$player->getName()} 加入卡牌游戏\n";
    }
}

// ==================== 运行示例 ====================

function main(): mixed
{
    echo "=== 卡牌游戏示例 ===\n\n";

    $room = new CardGameRoom('card_room_001', [
        'rounds' => 2,
        'turn_timeout' => 3,
        'min_players' => 2
    ]);

    // 创建玩家
    $players = [
        new Player('p1', null, 'Alice'),
        new Player('p2', null, 'Bob'),
        new Player('p3', null, 'Charlie'),
    ];

    foreach ($players as $player) {
        $room->addPlayer($player);
    }

    echo "玩家数: {$room->getPlayerCount()}\n\n";

    // 启动游戏
    $room->start();

    echo "\n卡牌游戏已结束\n";
}

run(main(...));

