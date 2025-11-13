<?php

namespace PfinalClub\AsyncioGamekit\RateLimit;

/**
 * 令牌桶限流器
 * 
 * 原理：
 * - 令牌桶以固定速率补充令牌
 * - 每次请求消耗一个令牌
 * - 桶满时停止补充
 * - 无令牌时请求被拒绝
 */
class TokenBucketLimiter implements RateLimiterInterface
{
    /** @var array<string, array{tokens: float, last_update: float}> 令牌桶状态 */
    private array $buckets = [];

    /** @var int 最大存储的桶数量 */
    private int $maxBuckets = 10000;

    /**
     * 检查是否允许通过
     * 
     * @param string $key 限流键
     * @param int $capacity 桶容量
     * @param float $rate 每秒补充速率
     * @return bool 是否允许通过
     */
    public function allow(string $key, int $capacity, float $rate): bool
    {
        $now = microtime(true);
        
        // 获取或创建桶
        if (!isset($this->buckets[$key])) {
            $this->buckets[$key] = [
                'tokens' => (float)$capacity,
                'last_update' => $now
            ];
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
            
            // 限制桶数量，防止内存泄漏
            $this->cleanupOldBuckets();
            
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
    }

    /**
     * 清理旧的桶（LRU策略）
     */
    private function cleanupOldBuckets(): void
    {
        if (count($this->buckets) <= $this->maxBuckets) {
            return;
        }

        // 找出最旧的10%桶并删除
        $toRemove = (int)(count($this->buckets) * 0.1);
        
        // 按最后更新时间排序
        uasort($this->buckets, fn($a, $b) => $a['last_update'] <=> $b['last_update']);
        
        // 删除最旧的桶
        $keys = array_keys($this->buckets);
        for ($i = 0; $i < $toRemove; $i++) {
            unset($this->buckets[$keys[$i]]);
        }
    }

    /**
     * 设置最大桶数量
     */
    public function setMaxBuckets(int $max): void
    {
        $this->maxBuckets = $max;
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
            'memory_usage' => memory_get_usage(true),
        ];
    }
}

