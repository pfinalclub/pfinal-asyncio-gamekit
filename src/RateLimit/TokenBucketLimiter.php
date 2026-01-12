<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\RateLimit;

/**
 * 令牌桶限流器
 * 
 * 原理：
 * - 令牌桶以固定速率补充令牌
 * - 每次请求消耗一个令牌
 * - 桶满时停止补充
 * - 无令牌时请求被拒绝
 * 
 * v3.1 优化：
 * - 使用 SplMinHeap 替代 uasort 提升清理性能
 * - 基于过期时间而非LRU策略清理
 */
class TokenBucketLimiter implements RateLimiterInterface
{
    /** @var array<string, array{tokens: float, last_update: float, heap_index: int}> 令牌桶状态 */
    private array $buckets = [];

    /** @var \SplMinHeap|null 过期时间最小堆 */
    private ?\SplMinHeap $expiryHeap = null;

    /** @var int 最大存储的桶数量 */
    private int $maxBuckets = 10000;

    /** @var float 桶过期时间（秒，不活动超过此时间的桶将被清理） */
    private float $bucketTTL = 3600; // 1小时
    
    /** @var int 清理计数器（降低清理频率） */
    private int $cleanupCounter = 0;
    
    /** @var int 清理间隔（每 N 次调用才清理一次） */
    private const CLEANUP_INTERVAL = 100;

    public function __construct()
    {
        $this->expiryHeap = new class extends \SplMinHeap {
            public function compare($value1, $value2): int
            {
                // 比较过期时间
                return $value2['last_update'] <=> $value1['last_update'];
            }
        };
    }

    /**
     * 检查是否允许通过（使用配置对象）
     * 
     * @param RateLimitConfig $config 限流配置
     * @return bool 是否允许通过
     */
    public function allow(RateLimitConfig $config): bool
    {
        return $this->checkToken($config->key, $config->capacity, $config->rate);
    }

    /**
     * 检查令牌（内部方法）
     * 
     * @param string $key 限流键
     * @param int $capacity 桶容量
     * @param float $rate 每秒补充速率
     * @return bool 是否允许通过
     */
    private function checkToken(string $key, int $capacity, float $rate): bool
    {
        $now = microtime(true);
        
        // 获取或创建桶
        if (!isset($this->buckets[$key])) {
            $this->buckets[$key] = [
                'tokens' => (float)$capacity,
                'last_update' => $now,
                'heap_index' => -1
            ];
            
            // 添加到过期堆
            $this->expiryHeap->insert([
                'key' => $key,
                'last_update' => $now
            ]);
        }

        $bucket = &$this->buckets[$key];
        
        // 计算经过的时间
        $elapsed = $now - $bucket['last_update'];
        
        // 补充令牌
        $bucket['tokens'] = min(
            (float)$capacity,
            $bucket['tokens'] + $elapsed * $rate
        );
        $bucket['last_update'] = $now;
        
        // 尝试消费一个令牌
        if ($bucket['tokens'] >= 1.0) {
            $bucket['tokens'] -= 1.0;
            
            // 【性能优化】降低清理频率，每 N 次调用才清理一次
            $this->cleanupCounter++;
            if ($this->cleanupCounter >= self::CLEANUP_INTERVAL) {
                $this->cleanupCounter = 0;
                $this->cleanupExpiredBuckets($now);
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * 重置指定键的限流状态
     */
    public function reset(string $key): void
    {
        unset($this->buckets[$key]);
        // 注意：堆中的条目会在下次清理时移除
    }

    /**
     * 获取剩余令牌数
     */
    public function remaining(string $key): float
    {
        if (!isset($this->buckets[$key])) {
            return 0.0;
        }
        
        return max(0.0, $this->buckets[$key]['tokens']);
    }

    /**
     * 清除所有限流状态
     */
    public function clear(): void
    {
        $this->buckets = [];
        $this->expiryHeap = new class extends \SplMinHeap {
            public function compare($value1, $value2): int
            {
                return $value2['last_update'] <=> $value1['last_update'];
            }
        };
    }

    /**
     * 清理过期的桶（基于时间的策略，性能更好）
     * 
     * @param float $now 当前时间
     */
    private function cleanupExpiredBuckets(float $now): void
    {
        // 达到桶数量限制时才进行清理
        if (count($this->buckets) < $this->maxBuckets) {
            return;
        }

        $threshold = $now - $this->bucketTTL;
        $removed = 0;
        $maxRemove = (int)(count($this->buckets) * 0.2); // 每次最多清理20%
        
        // 从堆顶移除过期的桶（O(log n)复杂度）
        while (!$this->expiryHeap->isEmpty() && $removed < $maxRemove) {
            $item = $this->expiryHeap->top();
            
            // 如果堆顶元素还未过期，说明后面的都不会过期
            if ($item['last_update'] >= $threshold) {
                break;
            }
            
            $this->expiryHeap->extract();
            $key = $item['key'];
            
            // 检查桶是否仍然存在且确实过期
            if (isset($this->buckets[$key]) && $this->buckets[$key]['last_update'] < $threshold) {
                unset($this->buckets[$key]);
                $removed++;
            }
        }
    }

    /**
     * 设置最大桶数量
     */
    public function setMaxBuckets(int $max): void
    {
        $this->maxBuckets = max(100, $max);
    }

    /**
     * 设置桶过期时间
     * 
     * @param float $seconds 过期时间（秒）
     */
    public function setBucketTTL(float $seconds): void
    {
        $this->bucketTTL = max(60, $seconds);
    }

    /**
     * 获取当前桶数量
     */
    public function getBucketCount(): int
    {
        return count($this->buckets);
    }

    /**
     * 获取所有限流统计信息
     */
    public function getStats(): array
    {
        return [
            'total_buckets' => count($this->buckets),
            'max_buckets' => $this->maxBuckets,
            'bucket_ttl' => $this->bucketTTL,
            'memory_usage' => memory_get_usage(true),
            'heap_size' => $this->expiryHeap->count(),
        ];
    }
}
