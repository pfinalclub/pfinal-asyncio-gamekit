<?php

/**
 * pfinal-asyncio-gamekit v3.0 新特性示例
 * 展示如何使用 Context 系统、结构化并发等新特性
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PfinalClub\AsyncioGamekit\Room\{Room, EnhancedRoom};
use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\GameServer;
use function PfinalClub\Asyncio\{run, set_context, get_context, create_task, gather, sleep};
use PfinalClub\Asyncio\Concurrency\GatherStrategy;

/**
 * 使用 v3.0 新特性的游戏房间
 */
class V30DemoRoom extends Room
{
    private array $gameState = [];
    private array $playerStats = [];

    protected function getDefaultConfig(): array
    {
        return [
            'max_players' => 4,
            'min_players' => 2,
            'game_duration' => 30, // 30秒游戏
            'enable_context' => true,
        ];
    }

    protected function onCreate(): mixed
    {
        // 使用 Context 系统设置房间初始状态
        set_context('room_start_time', microtime(true));
        set_context('game_phase', 'initializing');
        
        $this->gameState = [
            'round' => 0,
            'scores' => [],
            'events' => [],
        ];
        
        $this->broadcast('v3:room:created', [
            'message' => '房间创建成功！使用 v3.0 新特性',
            'features' => ['context_tracking', 'enhanced_concurrency', 'performance_monitoring'],
            'context' => [
                'room_start_time' => get_context('room_start_time'),
                'game_phase' => get_context('game_phase'),
            ]
        ]);
        
        set_context('game_phase', 'waiting');
        return null;
    }

    protected function onStart(): mixed
    {
        set_context('game_start_time', microtime(true));
        set_context('game_phase', 'starting');
        
        // 初始化玩家统计
        foreach ($this->players as $player) {
            $this->playerStats[$player->getId()] = [
                'score' => 0,
                'actions' => 0,
                'join_time' => microtime(true),
            ];
        }
        
        set_context('game_phase', 'running');
        
        $this->broadcast('v3:game:start', [
            'message' => '游戏开始！展示了 v3.0 的新特性',
            'players' => count($this->players),
            'context_data' => [
                'start_time' => get_context('game_start_time'),
                'phase' => get_context('game_phase'),
            ]
        ]);
        
        sleep(1);
        return null;
    }

    protected function run(): mixed
    {
        set_context('game_phase', 'main_loop');
        
        $gameDuration = $this->config['game_duration'];
        $startTime = microtime(true);
        
        // 使用结构化并发进行并发任务
        $this->runConcurrentGameLogic();
        
        // 演示 Gather 策略
        $this->demonstrateGatherStrategy();
        
        // 主游戏循环
        while ((microtime(true) - $startTime) < $gameDuration && $this->getPlayerCount() > 0) {
            $this->runGameRound();
            sleep(2);
        }
        
        // 游戏结束
        $this->endGame();
        
        return null;
    }

    /**
     * 演示结构化并发
     */
    private function runConcurrentGameLogic(): void
    {
        set_context('concurrent_tasks_start', microtime(true));
        
        // 创建并发任务
        $tasks = [];
        
        // 任务1: 生成随机事件
        $tasks[] = create_task(function() {
            $events = ['bonus', 'challenge', 'powerup', 'surprise'];
            $event = $events[array_rand($events)];
            
            set_context('generated_event', $event);
            sleep(0.5); // 模拟处理时间
            
            return [
                'type' => 'event',
                'data' => $event,
                'timestamp' => microtime(true),
            ];
        });
        
        // 任务2: 计算房间统计
        $tasks[] = create_task(function() {
            sleep(0.3); // 模拟计算时间
            
            return [
                'type' => 'stats',
                'data' => [
                    'player_count' => count($this->players),
                    'memory_usage' => memory_get_usage(true),
                    'uptime' => microtime(true) - get_context('room_start_time'),
                ],
                'timestamp' => microtime(true),
            ];
        });
        
        // 任务3: 检查游戏状态
        $tasks[] = create_task(function() {
            sleep(0.2);
            
            return [
                'type' => 'health',
                'data' => [
                    'phase' => get_context('game_phase'),
                    'room_age' => microtime(true) - get_context('room_start_time'),
                    'context_integrity' => get_context('room_start_time') !== null,
                ],
                'timestamp' => microtime(true),
            ];
        });
        
        // 使用 RETURN_PARTIAL 策略获取所有成功的结果
        $results = gather(...$tasks, GatherStrategy::RETURN_PARTIAL);
        
        // 记录结果到 Context
        set_context('concurrent_results', $results);
        set_context('concurrent_tasks_duration', microtime(true) - get_context('concurrent_tasks_start'));
        
        $this->broadcast('v3:concurrent:completed', [
            'message' => '并发任务完成',
            'results_count' => count($results),
            'strategy' => 'RETURN_PARTIAL',
            'duration' => round((microtime(true) - get_context('concurrent_tasks_start')) * 1000, 2) . 'ms',
            'context_data' => [
                'generated_event' => get_context('generated_event'),
                'results' => $results,
            ]
        ]);
    }

    /**
     * 演示不同的 Gather 策略
     */
    private function demonstrateGatherStrategy(): void
    {
        $strategies = [
            GatherStrategy::WAIT_ALL,
            GatherStrategy::FAIL_FAST,
            GatherStrategy::RETURN_PARTIAL,
        ];
        
        foreach ($strategies as $strategy) {
            $this->testGatherStrategy($strategy);
            sleep(0.1);
        }
    }

    private function testGatherStrategy(GatherStrategy $strategy): void
    {
        $strategyName = $strategy->name;
        
        try {
            $tasks = [];
            for ($i = 0; $i < 3; $i++) {
                $taskId = "strategy_test_{$strategyName}_{$i}";
                $tasks[] = create_task(function() use ($taskId, $i) {
                    set_context($taskId . '_start', microtime(true));
                    
                    if ($i === 1) {
                        // 第二个任务失败，测试策略行为
                        throw new \Exception("Task $i failed intentionally");
                    }
                    
                    sleep(0.1);
                    set_context($taskId . '_end', microtime(true));
                    
                    return [
                        'task_id' => $taskId,
                        'duration' => microtime(true) - get_context($taskId . '_start'),
                        'success' => true,
                    ];
                });
            }
            
            $results = gather(...$tasks, $strategy);
            
            $this->broadcast('v3:gather:strategy', [
                'strategy' => $strategyName,
                'results' => $results,
                'message' => "测试 $strategyName 策略成功",
            ]);
            
        } catch (\Exception $e) {
            $this->broadcast('v3:gather:strategy', [
                'strategy' => $strategyName,
                'error' => $e->getMessage(),
                'message' => "测试 $strategyName 策略时发生异常",
            ]);
        }
    }

    private function runGameRound(): void
    {
        $round = ++$this->gameState['round'];
        set_context('current_round', $round);
        
        $this->gameState['round'] = $round;
        $this->gameState['events'][] = [
            'type' => 'round_start',
            'round' => $round,
            'timestamp' => microtime(true),
        ];
        
        // 随机给玩家加分
        if (!empty($this->players)) {
            $randomPlayer = array_rand($this->players);
            $playerId = $randomPlayer->getId();
            
            if (isset($this->playerStats[$playerId])) {
                $this->playerStats[$playerId]['score'] += 10;
                $this->playerStats[$playerId]['actions']++;
                
                $this->gameState['events'][] = [
                    'type' => 'bonus',
                    'player_id' => $playerId,
                    'player_name' => $this->players[$playerId]->getName(),
                    'round' => $round,
                    'score_change' => '+10',
                    'timestamp' => microtime(true),
                ];
                
                $this->broadcast('v3:round:event', [
                    'round' => $round,
                    'event' => 'bonus',
                    'player' => $this->players[$playerId]->toArray(),
                    'score' => $this->playerStats[$playerId]['score'],
                    'context_data' => [
                        'current_round' => get_context('current_round'),
                        'total_rounds' => count($this->gameState['events']),
                    ]
                ]);
            }
        }
    }

    private function endGame(): void
    {
        set_context('game_phase', 'ending');
        $gameDuration = microtime(true) - get_context('room_start_time');
        
        // 计算最终得分
        $finalScores = [];
        foreach ($this->players as $player) {
            $playerId = $player->getId();
            if (isset($this->playerStats[$playerId])) {
                $finalScores[] = [
                    'player' => $player->toArray(),
                    'stats' => $this->playerStats[$playerId],
                ];
            }
        }
        
        // 按得分排序
        usort($finalScores, fn($a, $b) => $b['stats']['score'] <=> $a['stats']['score']);
        
        $winner = $finalScores[0] ?? null;
        
        $this->broadcast('v3:game:end', [
            'message' => '游戏结束！',
            'duration' => round($gameDuration, 2) . 's',
            'rounds' => $this->gameState['round'],
            'events_count' => count($this->gameState['events']),
            'winner' => $winner,
            'all_scores' => $finalScores,
            'context_summary' => [
                'room_duration' => round($gameDuration, 2) . 's',
                'concurrent_tasks_duration' => round(get_context('concurrent_tasks_duration'), 2) . 'ms',
                'gather_strategies_tested' => true,
                'v3_features_used' => ['context_tracking', 'structured_concurrency', 'enhanced_error_handling'],
            ]
        ]);
        
        // 清理 Context（演示 Context 管理）
        delete_context('room_start_time');
        delete_context('game_start_time');
        delete_context('game_phase');
        delete_context('current_round');
        delete_context('generated_event');
        delete_context('concurrent_results');
        delete_context('concurrent_tasks_start');
        delete_context('concurrent_tasks_duration');
        
        set_context('game_phase', 'completed');
    }

    protected function onDestroy(): mixed
    {
        $this->broadcast('v3:room:destroyed', [
            'message' => '房间销毁',
            'final_context_state' => get_all_context(false),
            'v3_features_demonstrated' => true,
        ]);
        
        clear_context();
        return null;
    }

    public function onPlayerMessage(Player $player, string $event, mixed $data): mixed
    {
        // 使用 Context 追踪玩家交互
        $playerContext = [
            'player_id' => $player->getId(),
            'event' => $event,
            'data' => $data,
            'timestamp' => microtime(true),
            'room_phase' => get_context('game_phase', 'unknown'),
        ];
        
        // 记录到 Context
        set_context("player_action_{$player->getId()}_{$this->gameState['round']}", $playerContext);
        
        if ($event === 'get_stats') {
            $player->send('v3:player:stats', [
                'stats' => $this->playerStats[$player->getId()] ?? [],
                'room_context' => [
                    'room_id' => $this->id,
                    'phase' => get_context('game_phase'),
                    'round' => get_context('current_round'),
                ]
            ]);
        }
        
        return null;
    }
}

// 主程序
function main(): mixed
{
    echo "=== pfinal-asyncio-gamekit v3.0 新特性演示 ===\n\n";
    
    $room = new V30DemoRoom('v3_demo_room', [
        'enable_context' => true,
        'game_duration' => 15, // 15秒演示
    ]);
    
    // 创建玩家
    $players = [
        new Player('player1', null, 'Alice'),
        new Player('player2', null, 'Bob'),
        new Player('player3', null, 'Charlie'),
    ];
    
    // 添加玩家
    foreach ($players as $player) {
        $room->addPlayer($player);
    }
    
    echo "房间创建完成，开始演示 v3.0 新特性...\n";
    
    // 启动游戏
    $room->start();
    
    echo "\n演示完成！\n";
    echo "主要演示的 v3.0 特性：\n";
    echo "1. Context 系统 - 协程本地变量追踪\n";
    echo "2. 结构化并发 - GatherStrategy 支持\n";
    echo "3. 性能优化 - 并发任务处理\n";
    echo "4. 增强错误处理 - 优雅的异常管理\n";
    
    return null;
}

// 运行
run(main(...));