<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Middleware;

use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\Logger\LoggerFactory;

/**
 * 性能监控中间件
 * 监控消息处理性能，记录慢查询
 */
class PerformanceMiddleware implements MiddlewareInterface
{
    /** @var float 慢查询阈值（秒） */
    private float $slowThreshold;

    /** @var array 性能统计 */
    private array $stats = [];

    public function __construct(float $slowThreshold = 1.0)
    {
        $this->slowThreshold = $slowThreshold;
    }

    public function handle(Player $player, string $event, mixed $data, callable $next): mixed
    {
        $startTime = microtime(true);
        $memoryBefore = memory_get_usage();

        try {
            $result = $next($player, $event, $data);

            $duration = microtime(true) - $startTime;
            $memoryUsed = memory_get_usage() - $memoryBefore;

            $this->recordStats($event, $duration, $memoryUsed, true);

            // 记录慢查询
            if ($duration > $this->slowThreshold) {
                LoggerFactory::warning("Slow message processing detected", [
                    'event' => $event,
                    'player_id' => $player->getId(),
                    'duration' => round($duration, 4),
                    'threshold' => $this->slowThreshold,
                    'memory_used_bytes' => $memoryUsed,
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;
            $memoryUsed = memory_get_usage() - $memoryBefore;

            $this->recordStats($event, $duration, $memoryUsed, false);

            throw $e;
        }
    }

    /**
     * 记录统计信息
     */
    private function recordStats(string $event, float $duration, int $memoryUsed, bool $success): void
    {
        if (!isset($this->stats[$event])) {
            $this->stats[$event] = [
                'count' => 0,
                'success_count' => 0,
                'error_count' => 0,
                'total_duration' => 0,
                'max_duration' => 0,
                'min_duration' => PHP_FLOAT_MAX,
                'total_memory' => 0,
            ];
        }

        $this->stats[$event]['count']++;
        
        if ($success) {
            $this->stats[$event]['success_count']++;
        } else {
            $this->stats[$event]['error_count']++;
        }

        $this->stats[$event]['total_duration'] += $duration;
        $this->stats[$event]['max_duration'] = max($this->stats[$event]['max_duration'], $duration);
        $this->stats[$event]['min_duration'] = min($this->stats[$event]['min_duration'], $duration);
        $this->stats[$event]['total_memory'] += $memoryUsed;
    }

    /**
     * 获取统计信息
     */
    public function getStats(): array
    {
        $result = [];

        foreach ($this->stats as $event => $stat) {
            $avgDuration = $stat['count'] > 0 ? $stat['total_duration'] / $stat['count'] : 0;
            $avgMemory = $stat['count'] > 0 ? $stat['total_memory'] / $stat['count'] : 0;

            $result[$event] = [
                'count' => $stat['count'],
                'success_count' => $stat['success_count'],
                'error_count' => $stat['error_count'],
                'success_rate' => $stat['count'] > 0 ? round($stat['success_count'] / $stat['count'] * 100, 2) : 0,
                'avg_duration_ms' => round($avgDuration * 1000, 2),
                'max_duration_ms' => round($stat['max_duration'] * 1000, 2),
                'min_duration_ms' => round($stat['min_duration'] * 1000, 2),
                'avg_memory_kb' => round($avgMemory / 1024, 2),
            ];
        }

        return $result;
    }

    /**
     * 重置统计信息
     */
    public function resetStats(): void
    {
        $this->stats = [];
    }

    /**
     * 设置慢查询阈值
     */
    public function setSlowThreshold(float $threshold): void
    {
        $this->slowThreshold = $threshold;
    }
}

