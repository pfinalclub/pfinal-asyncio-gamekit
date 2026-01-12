<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Room;

use PfinalClub\AsyncioGamekit\Player;
use function PfinalClub\Asyncio\{set_context, get_context, create_task, gather};
use PfinalClub\Asyncio\Concurrency\GatherStrategy;

/**
 * 增强的房间基类（集成 v3.0 新特性）
 * 提供更便捷的 Context 和并发操作
 */
abstract class EnhancedRoom extends Room
{
    /**
     * 创建房间时自动设置上下文
     */
    public function __construct(string $id, array $config = [])
    {
        // 设置房间上下文
        set_context('room_id', $id);
        set_context('room_created_at', microtime(true));
        set_context('room_config', $config);
        
        parent::__construct($id, $config);
    }
    
    /**
     * 带追踪的快速匹配
     */
    public function quickMatchWithTrace(Player $player, string $roomClass, array $config = []): ?Room
    {
        // 设置匹配追踪上下文
        set_context('match_request_id', uniqid());
        set_context('player_id', $player->getId());
        set_context('match_class', $roomClass);
        set_context('match_time', microtime(true));
        
        // 记录匹配开始
        LoggerFactory::info("Starting quick match for player {player_id}", [
            'player_id' => $player->getId(),
            'player_name' => $player->getName(),
            'request_id' => get_context('match_request_id'),
            'room_class' => $roomClass,
        ]);
        
        // 执行匹配逻辑（这里简化为直接创建房间）
        $room = $this->createRoomWithTrace($roomClass, $config);
        
        if ($room) {
            // 记录匹配成功
            $matchTime = microtime(true) - get_context('match_time');
            set_context('match_duration', $matchTime);
            
            LoggerFactory::info("Quick match completed for player {player_id}", [
                'player_id' => $player->getId(),
                'room_id' => $room->getId(),
                'duration' => round($matchTime * 1000, 2) . 'ms',
            ]);
        }
        
        return $room;
    }
    
    /**
     * 带追踪的房间创建
     */
    protected function createRoomWithTrace(string $roomClass, array $config = []): ?Room
    {
        set_context('room_creation_started', microtime(true));
        
        try {
            // 创建房间
            $room = new $roomClass(
                get_context('room_id') . '_' . uniqid(),
                array_merge(get_context('room_config', []), $config)
            );
            
            // 设置房间创建时间
            $creationTime = microtime(true) - get_context('room_creation_started');
            set_context('room_creation_duration', $creationTime);
            
            LoggerFactory::info("Room {room_id} created with new features", [
                'room_id' => $room->getId(),
                'creation_duration' => round($creationTime * 1000, 2) . 'ms',
                'features_used' => ['context_tracking', 'v3_asyncio'],
            ]);
            
            return $room;
            
        } catch (\Throwable $e) {
            // 记录创建失败
            $creationTime = microtime(true) - get_context('room_creation_started');
            
            LoggerFactory::error("Failed to create room {room_class}", [
                'room_class' => $roomClass,
                'duration' => round($creationTime * 1000, 2) . 'ms',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return null;
        }
    }
    
    /**
     * 优化的并发广播
     * 使用 GatherStrategy 提升性能
     */
    protected function concurrentBroadcast(array $events): void
    {
        if (empty($this->players)) {
            return;
        }
        
        $broadcastTime = microtime(true);
        $taskCount = 0;
        
        try {
            // 为每个事件创建并发任务
            $tasks = [];
            foreach ($events as $event) {
                $tasks[] = create_task(function() use ($event) {
                    $this->broadcast($event['name'], $event['data'] ?? null);
                    return true;
                });
                $taskCount++;
            }
            
            // 使用 RETURN_PARTIAL 策略，确保成功的广播都能完成
            $results = gather(...$tasks, GatherStrategy::RETURN_PARTIAL);
            
            $successCount = count(array_filter($results));
            $duration = microtime(true) - $broadcastTime;
            
            // 记录广播统计
            LoggerFactory::info("Concurrent broadcast completed", [
                'room_id' => $this->id,
                'total_events' => $taskCount,
                'successful' => $successCount,
                'duration' => round($duration * 1000, 2) . 'ms',
                'strategy' => 'RETURN_PARTIAL',
            ]);
            
        } catch (\Throwable $e) {
            $duration = microtime(true) - $broadcastTime;
            
            LoggerFactory::error("Concurrent broadcast failed", [
                'room_id' => $this->id,
                'duration' => round($duration * 1000, 2) . 'ms',
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * 性能监控的玩家加入
     */
    public function addPlayerWithMonitoring(Player $player): bool
    {
        $joinStartTime = microtime(true);
        
        try {
            // 设置玩家上下文
            set_context('player_join_time_' . $player->getId(), $joinStartTime);
            set_context('player_join_attempt_' . $player->getId(), true);
            
            // 执行加入逻辑
            $result = parent::addPlayer($player);
            
            $joinDuration = microtime(true) - $joinStartTime;
            
            if ($result) {
                LoggerFactory::info("Player {player_name} joined room {room_id} successfully", [
                    'room_id' => $this->id,
                    'player_id' => $player->getId(),
                    'player_name' => $player->getName(),
                    'join_duration' => round($joinDuration * 1000, 2) . 'ms',
                    'current_players' => $this->getPlayerCount(),
                ]);
                
                // 记录成功加入的上下文
                set_context('player_joined_' . $player->getId(), true);
            } else {
                LoggerFactory::warning("Failed to add player {player_name} to room {room_id}", [
                    'room_id' => $this->id,
                    'player_id' => $player->getId(),
                    'player_name' => $player->getName(),
                    'join_duration' => round($joinDuration * 1000, 2) . 'ms',
                ]);
            }
            
            return $result;
            
        } catch (\Throwable $e) {
            $joinDuration = microtime(true) - $joinStartTime;
            
            LoggerFactory::error("Exception during player join", [
                'room_id' => $this->id,
                'player_id' => $player->getId(),
                'player_name' => $player->getName(),
                'duration' => round($joinDuration * 1000, 2) . 'ms',
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * 获取房间性能统计
     */
    public function getPerformanceStats(): array
    {
        $now = microtime(true);
        $roomAge = $now - get_context('room_created_at', $now);
        
        return [
            'room_id' => $this->id,
            'status' => $this->getStatus(),
            'player_count' => $this->getPlayerCount(),
            'room_age_seconds' => round($roomAge, 2),
            'context_data' => [
                'has_room_context' => get_context('room_id') === $this->id,
                'has_match_trace' => get_context('match_request_id') !== null,
                'room_created_at' => get_context('room_created_at'),
            ],
            'v3_features' => [
                'context_tracking' => true,
                'enhanced_concurrency' => true,
                'performance_monitoring' => true,
            ],
        ];
    }
    
    /**
     * 清理房间上下文
     */
    protected function onRoomComplete(): void
    {
        $roomAge = microtime(true) - get_context('room_created_at', microtime(true));
        
        LoggerFactory::info("Room {room_id} completed after {duration}s", [
            'room_id' => $this->id,
            'duration' => round($roomAge, 2),
            'final_player_count' => $this->getPlayerCount(),
            'v3_features_used' => 'context_tracking, enhanced_concurrency, performance_monitoring',
        ]);
        
        // 清理房间相关的上下文
        delete_context('room_created_at');
        delete_context('room_config');
        delete_context('match_request_id');
        delete_context('match_duration');
    }
}