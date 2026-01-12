<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Tests;

use PHPUnit\Framework\TestCase;

/**
 * v3.0 新特性测试
 * 测试 pfinal-asyncio v3.0 的新特性和改进
 */
class V30FeaturesTest extends TestCase
{
    public function testAsyncioV3Available(): void
    {
        // 测试 pfinal-asyncio v3.0 是否可用
        $this->assertTrue(class_exists('PfinalClub\\Asyncio\\EventLoop'));
        $this->assertTrue(class_exists('PfinalClub\\Asyncio\\Core\\Task'));
        $this->assertTrue(class_exists('PfinalClub\\Asyncio\\Concurrency\\GatherStrategy'));
    }
    
    public function testCoreFunctionsAvailable(): void
    {
        // 测试核心函数是否可用
        $this->assertTrue(function_exists('PfinalClub\\Asyncio\\run'));
        $this->assertTrue(function_exists('PfinalClub\\Asyncio\\create_task'));
        $this->assertTrue(function_exists('PfinalClub\\Asyncio\\sleep'));
        $this->assertTrue(function_exists('PfinalClub\\Asyncio\\gather'));
        $this->assertTrue(function_exists('PfinalClub\\Asyncio\\await'));
    }
    
    public function testContextFunctionsAvailable(): void
    {
        // 测试 Context 相关函数是否可用
        $this->assertTrue(function_exists('PfinalClub\\Asyncio\\set_context'));
        $this->assertTrue(function_exists('PfinalClub\\Asyncio\\get_context'));
        $this->assertTrue(function_exists('PfinalClub\\Asyncio\\has_context'));
        $this->assertTrue(function_exists('PfinalClub\\Asyncio\\delete_context'));
        $this->assertTrue(function_exists('PfinalClub\\Asyncio\\get_all_context'));
        $this->assertTrue(function_exists('PfinalClub\\Asyncio\\clear_context'));
    }
    
    public function testConcurrencyClassesAvailable(): void
    {
        // 测试并发相关类是否可用
        $this->assertTrue(class_exists('PfinalClub\\Asyncio\\Concurrency\\CancellationScope'));
        $this->assertTrue(class_exists('PfinalClub\\Asyncio\\Concurrency\\TaskGroup'));
        $this->assertTrue(class_exists('PfinalClub\\Asyncio\\Concurrency\\GatherStrategy'));
        $this->assertTrue(class_exists('PfinalClub\\Asyncio\\Exception\\GatherException'));
    }
    
    public function testResourceClassesAvailable(): void
    {
        // 测试资源管理相关类是否可用
        $this->assertTrue(class_exists('PfinalClub\\Asyncio\\Resource\\Context'));
        $this->assertTrue(class_exists('PfinalClub\\Asyncio\\Resource\\AsyncResource'));
        $this->assertTrue(class_exists('PfinalClub\\Asyncio\\Resource\\AsyncResourceManager'));
    }
    
    public function testEventLoopOptimization(): void
    {
        // 测试事件循环优化
        $loop = \PfinalClub\Asyncio\get_event_loop();
        $this->assertInstanceOf(\PfinalClub\Asyncio\Core\EventLoopInterface::class, $loop);
        
        // 测试是否有性能优化提示
        $reflection = new \ReflectionClass($loop);
        $this->assertTrue($reflection->hasMethod('getStats') || $reflection->hasMethod('getInfo'));
    }
}