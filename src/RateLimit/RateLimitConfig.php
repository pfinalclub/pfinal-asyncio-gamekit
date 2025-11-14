<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\RateLimit;

use PfinalClub\AsyncioGamekit\Constants\GameConfig;

/**
 * 限流配置值对象
 * 封装限流参数，避免方法参数过多
 */
final class RateLimitConfig
{
    /**
     * @param int $capacity 令牌桶容量
     * @param float $rate 令牌生成速率（每秒）
     * @param string $key 限流标识（如玩家ID、IP地址）
     */
    public function __construct(
        public readonly int $capacity,
        public readonly float $rate,
        public readonly string $key
    ) {
        if ($capacity <= 0) {
            throw new \InvalidArgumentException("Capacity must be positive, got: {$capacity}");
        }
        
        if ($rate <= 0) {
            throw new \InvalidArgumentException("Rate must be positive, got: {$rate}");
        }
        
        if (empty($key)) {
            throw new \InvalidArgumentException("Key cannot be empty");
        }
    }
    
    /**
     * 使用默认配置创建
     */
    public static function withDefaults(string $key): self
    {
        return new self(
            GameConfig::RATE_LIMIT_CAPACITY_DEFAULT,
            GameConfig::RATE_LIMIT_RATE_DEFAULT,
            $key
        );
    }
    
    /**
     * 创建自定义配置
     * 
     * @param string $key 限流标识
     * @param int $capacity 令牌桶容量
     * @param float $rate 令牌生成速率（每秒）
     */
    public static function custom(string $key, int $capacity, float $rate): self
    {
        return new self(
            capacity: $capacity,
            rate: $rate,
            key: $key
        );
    }
}

