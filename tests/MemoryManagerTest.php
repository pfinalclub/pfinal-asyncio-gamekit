<?php

namespace PfinalClub\AsyncioGamekit\Tests;

use PHPUnit\Framework\TestCase;
use PfinalClub\AsyncioGamekit\Memory\MemoryManager;

class MemoryManagerTest extends TestCase
{
    public function testConstructor(): void
    {
        $manager = new MemoryManager(128, 0.8);
        
        $this->assertEquals(128 * 1024 * 1024, $manager->getLimit());
    }

    public function testGetCurrentUsage(): void
    {
        $manager = new MemoryManager();
        
        $usage = $manager->getCurrentUsage();
        
        $this->assertGreaterThan(0, $usage);
    }

    public function testGetUsagePercentage(): void
    {
        $manager = new MemoryManager(256);
        
        $percentage = $manager->getUsagePercentage();
        
        $this->assertGreaterThanOrEqual(0, $percentage);
        $this->assertLessThanOrEqual(100, $percentage);
    }

    public function testGetUsagePercentageWithNoLimit(): void
    {
        $manager = new MemoryManager(0); // 不限制
        
        $percentage = $manager->getUsagePercentage();
        
        $this->assertEquals(0.0, $percentage);
    }

    public function testIsOverLimitWithNoLimit(): void
    {
        $manager = new MemoryManager(0);
        
        $this->assertFalse($manager->isOverLimit());
    }

    public function testIsOverLimitWithHighLimit(): void
    {
        $manager = new MemoryManager(10000); // 10GB
        
        $this->assertFalse($manager->isOverLimit());
    }

    public function testGarbageCollection(): void
    {
        $manager = new MemoryManager();
        
        // 创建一些垃圾数据
        $data = [];
        for ($i = 0; $i < 1000; $i++) {
            $data[] = str_repeat('x', 1000);
        }
        
        $beforeGc = $manager->getCurrentUsage();
        
        // 清除引用
        $data = null;
        
        $manager->gc();
        
        $afterGc = $manager->getCurrentUsage();
        
        // GC后内存应该减少或保持不变
        $this->assertLessThanOrEqual($beforeGc, $afterGc);
    }

    public function testGetStats(): void
    {
        $manager = new MemoryManager(256, 0.8);
        
        $stats = $manager->getStats();
        
        $this->assertArrayHasKey('current_bytes', $stats);
        $this->assertArrayHasKey('current_mb', $stats);
        $this->assertArrayHasKey('peak_bytes', $stats);
        $this->assertArrayHasKey('peak_mb', $stats);
        $this->assertArrayHasKey('limit_bytes', $stats);
        $this->assertArrayHasKey('limit_mb', $stats);
        $this->assertArrayHasKey('usage_percentage', $stats);
        $this->assertArrayHasKey('php_memory_limit', $stats);
    }

    public function testSetCheckInterval(): void
    {
        $manager = new MemoryManager();
        
        $manager->setCheckInterval(10);
        
        // 没有异常说明设置成功
        $this->assertTrue(true);
    }

    public function testSetWarningThreshold(): void
    {
        $manager = new MemoryManager();
        
        $manager->setWarningThreshold(0.9);
        
        // 没有异常说明设置成功
        $this->assertTrue(true);
    }

    public function testWarningThresholdBounds(): void
    {
        $manager = new MemoryManager(256, 1.5); // 超过1.0
        
        // 应该被限制在合理范围内，不抛出异常
        $this->assertTrue(true);
    }

    public function testPeriodicCheck(): void
    {
        $manager = new MemoryManager(10000);
        $manager->setCheckInterval(0); // 立即检查
        
        // 不应该抛出异常
        $manager->periodicCheck();
        
        $this->assertTrue(true);
    }
}

