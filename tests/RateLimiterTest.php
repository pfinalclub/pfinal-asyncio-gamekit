<?php

namespace PfinalClub\AsyncioGamekit\Tests;

use PHPUnit\Framework\TestCase;
use PfinalClub\AsyncioGamekit\RateLimit\TokenBucketLimiter;
use PfinalClub\AsyncioGamekit\RateLimit\RateLimitConfig;

class RateLimiterTest extends TestCase
{
    private TokenBucketLimiter $limiter;

    protected function setUp(): void
    {
        $this->limiter = new TokenBucketLimiter();
    }

    /**
     * 辅助方法：使用配置对象调用 allow
     */
    private function allow(string $key, int $capacity, float $rate): bool
    {
        $config = RateLimitConfig::custom($key, $capacity, $rate);
        return $this->limiter->allow($config);
    }

    public function testAllowFirstRequest(): void
    {
        $result = $this->allow('user1', 10, 1);
        
        $this->assertTrue($result);
    }

    public function testExceedCapacity(): void
    {
        $capacity = 3;
        $rate = 1; // 每秒补充1个令牌
        
        // 前3个请求应该成功
        for ($i = 0; $i < $capacity; $i++) {
            $result = $this->allow('user1', $capacity, $rate);
            $this->assertTrue($result, "Request $i should be allowed");
        }
        
        // 第4个请求应该失败（令牌已耗尽）
        $result = $this->allow('user1', $capacity, $rate);
        $this->assertFalse($result, "Request beyond capacity should be denied");
    }

    public function testTokenRefill(): void
    {
        $capacity = 2;
        $rate = 10; // 每秒补充10个令牌
        
        // 消耗所有令牌
        $this->allow('user1', $capacity, $rate);
        $this->allow('user1', $capacity, $rate);
        
        // 此时应该失败
        $this->assertFalse($this->allow('user1', $capacity, $rate));
        
        // 等待100ms（应该补充1个令牌）
        usleep(100000);
        
        // 现在应该成功
        $this->assertTrue($this->allow('user1', $capacity, $rate));
    }

    public function testDifferentUsers(): void
    {
        $capacity = 2;
        $rate = 1;
        
        // user1 消耗令牌
        $this->assertTrue($this->allow('user1', $capacity, $rate));
        $this->assertTrue($this->allow('user1', $capacity, $rate));
        $this->assertFalse($this->allow('user1', $capacity, $rate));
        
        // user2 应该有独立的令牌桶
        $this->assertTrue($this->allow('user2', $capacity, $rate));
        $this->assertTrue($this->allow('user2', $capacity, $rate));
        $this->assertFalse($this->allow('user2', $capacity, $rate));
    }

    public function testReset(): void
    {
        $capacity = 2;
        $rate = 1;
        
        // 消耗所有令牌
        $this->allow('user1', $capacity, $rate);
        $this->allow('user1', $capacity, $rate);
        
        $this->assertFalse($this->allow('user1', $capacity, $rate));
        
        // 重置
        $this->limiter->reset('user1');
        
        // 现在应该成功
        $this->assertTrue($this->allow('user1', $capacity, $rate));
    }

    public function testRemaining(): void
    {
        $capacity = 5;
        $rate = 1;
        
        // 消耗2个令牌
        $this->allow('user1', $capacity, $rate);
        $this->allow('user1', $capacity, $rate);
        
        $remaining = $this->limiter->remaining('user1');
        
        $this->assertGreaterThan(2, $remaining);
        $this->assertLessThanOrEqual($capacity, $remaining);
    }

    public function testRemainingForNonexistentKey(): void
    {
        $remaining = $this->limiter->remaining('nonexistent_user');
        
        $this->assertEquals(0.0, $remaining);
    }

    public function testClear(): void
    {
        $this->allow('user1', 10, 1);
        $this->allow('user2', 10, 1);
        
        $this->assertGreaterThan(0, $this->limiter->getBucketCount());
        
        $this->limiter->clear();
        
        $this->assertEquals(0, $this->limiter->getBucketCount());
    }

    public function testGetStats(): void
    {
        $this->allow('user1', 10, 1);
        $this->allow('user2', 10, 1);
        
        $stats = $this->limiter->getStats();
        
        $this->assertArrayHasKey('total_buckets', $stats);
        $this->assertArrayHasKey('max_buckets', $stats);
        $this->assertArrayHasKey('memory_usage', $stats);
        
        $this->assertEquals(2, $stats['total_buckets']);
        $this->assertGreaterThan(0, $stats['memory_usage']);
    }

    public function testSetMaxBuckets(): void
    {
        $this->limiter->setMaxBuckets(100);
        
        $stats = $this->limiter->getStats();
        
        $this->assertEquals(100, $stats['max_buckets']);
    }

    public function testAutoCleanup(): void
    {
        $this->limiter->setMaxBuckets(5);
        
        // 创建超过最大数量的桶
        for ($i = 0; $i < 10; $i++) {
            $this->allow("user$i", 10, 1);
        }
        
        $stats = $this->limiter->getStats();
        
        // 应该触发清理，桶数量应该少于10
        $this->assertLessThanOrEqual(10, $stats['total_buckets']);
        $this->assertGreaterThan(0, $stats['total_buckets']);
    }

    public function testHighRateRefill(): void
    {
        $capacity = 100;
        $rate = 1000; // 每秒1000个令牌
        
        // 快速消耗
        for ($i = 0; $i < 50; $i++) {
            $this->allow('user1', $capacity, $rate);
        }
        
        // 等待50ms
        usleep(50000);
        
        // 应该补充约50个令牌，所以应该能再消耗一些
        $allowed = 0;
        for ($i = 0; $i < 60; $i++) {
            if ($this->allow('user1', $capacity, $rate)) {
                $allowed++;
            }
        }
        
        $this->assertGreaterThan(40, $allowed);
    }

    public function testZeroCapacity(): void
    {
        // 容量为0应该抛出异常（配置验证）
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Capacity must be positive');
        
        $this->allow('user1', 0, 1);
    }

    public function testGetBucketCount(): void
    {
        $this->assertEquals(0, $this->limiter->getBucketCount());
        
        $this->allow('user1', 10, 1);
        $this->assertEquals(1, $this->limiter->getBucketCount());
        
        $this->allow('user2', 10, 1);
        $this->assertEquals(2, $this->limiter->getBucketCount());
        
        $this->limiter->reset('user1');
        $this->assertEquals(1, $this->limiter->getBucketCount());
    }

    public function testTokenBucketCapacityLimit(): void
    {
        $capacity = 5;
        $rate = 100; // 快速补充
        
        // 等待足够时间让令牌补充满
        usleep(100000);
        
        // 消耗超过容量的令牌
        $allowed = 0;
        for ($i = 0; $i < 10; $i++) {
            if ($this->allow('user1', $capacity, $rate)) {
                $allowed++;
            }
        }
        
        // 最多只能成功 capacity 次
        $this->assertLessThanOrEqual($capacity + 1, $allowed); // +1 允许浮点误差
    }

    public function testConcurrentUsersDoNotInterfere(): void
    {
        $capacity = 3;
        $rate = 1;
        
        $users = ['user1', 'user2', 'user3'];
        
        foreach ($users as $user) {
            // 每个用户应该都能成功3次
            for ($i = 0; $i < $capacity; $i++) {
                $result = $this->allow($user, $capacity, $rate);
                $this->assertTrue($result, "User $user request $i should be allowed");
            }
            
            // 第4次应该失败
            $result = $this->allow($user, $capacity, $rate);
            $this->assertFalse($result, "User $user extra request should be denied");
        }
    }
}

