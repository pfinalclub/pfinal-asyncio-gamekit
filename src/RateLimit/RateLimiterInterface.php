<?php

namespace PfinalClub\AsyncioGamekit\RateLimit;

/**
 * 限流器接口
 */
interface RateLimiterInterface
{
    /**
     * 检查是否允许通过（消费一个令牌）
     * 
     * @param string $key 限流键（如用户ID、IP地址等）
     * @param int $capacity 桶容量（最大令牌数）
     * @param float $rate 令牌补充速率（每秒补充数量）
     * @return bool 是否允许通过
     */
    public function allow(string $key, int $capacity, float $rate): bool;

    /**
     * 重置指定键的限流状态
     * 
     * @param string $key 限流键
     * @return void
     */
    public function reset(string $key): void;

    /**
     * 获取剩余令牌数
     * 
     * @param string $key 限流键
     * @return float 剩余令牌数
     */
    public function remaining(string $key): float;

    /**
     * 清除所有限流状态
     * 
     * @return void
     */
    public function clear(): void;
}

