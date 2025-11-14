<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\RateLimit;

/**
 * 限流器接口
 */
interface RateLimiterInterface
{
    /**
     * 检查是否允许通过（消费一个令牌）
     * 
     * @param RateLimitConfig $config 限流配置（包含key、capacity、rate）
     * @return bool 是否允许通过
     */
    public function allow(RateLimitConfig $config): bool;

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

