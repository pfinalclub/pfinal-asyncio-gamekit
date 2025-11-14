<?php

namespace PfinalClub\AsyncioGamekit\Tests;

use PHPUnit\Framework\TestCase;
use PfinalClub\AsyncioGamekit\Middleware\{
    MiddlewarePipeline,
    MiddlewareInterface,
    LoggingMiddleware,
    ValidationMiddleware,
    PerformanceMiddleware
};
use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\Exceptions\ServerException;

class MiddlewareTest extends TestCase
{
    private Player $player;

    protected function setUp(): void
    {
        $this->player = new Player('test_player', null, 'TestPlayer');
    }

    public function testPipelineAddMiddleware(): void
    {
        $pipeline = new MiddlewarePipeline();
        $middleware = new LoggingMiddleware();
        
        $pipeline->add($middleware);
        
        $this->assertEquals(1, $pipeline->count());
        $this->assertCount(1, $pipeline->getMiddlewares());
    }

    public function testPipelineAddMultipleMiddlewares(): void
    {
        $pipeline = new MiddlewarePipeline();
        
        $middlewares = [
            new LoggingMiddleware(),
            new PerformanceMiddleware(),
        ];
        
        $pipeline->addMultiple($middlewares);
        
        $this->assertEquals(2, $pipeline->count());
    }

    public function testPipelineProcess(): void
    {
        $pipeline = new MiddlewarePipeline();
        $executed = new \ArrayObject();
        
        // 创建测试中间件
        $middleware1 = new class($executed) implements MiddlewareInterface {
            private \ArrayObject $executed;
            
            public function __construct(\ArrayObject $executed)
            {
                $this->executed = $executed;
            }
            
            public function handle($player, $event, $data, callable $next): mixed
            {
                $this->executed[] = 'middleware1_before';
                $result = $next($player, $event, $data);
                $this->executed[] = 'middleware1_after';
                return $result;
            }
        };
        
        $middleware2 = new class($executed) implements MiddlewareInterface {
            private \ArrayObject $executed;
            
            public function __construct(\ArrayObject $executed)
            {
                $this->executed = $executed;
            }
            
            public function handle($player, $event, $data, callable $next): mixed
            {
                $this->executed[] = 'middleware2_before';
                $result = $next($player, $event, $data);
                $this->executed[] = 'middleware2_after';
                return $result;
            }
        };
        
        $pipeline->add($middleware1);
        $pipeline->add($middleware2);
        
        $finalHandler = function($player, $event, $data) use ($executed) {
            $executed[] = 'handler';
            return 'result';
        };
        
        $result = $pipeline->process($this->player, 'test', [], $finalHandler);
        
        // 验证执行顺序（洋葱模型）
        $this->assertEquals([
            'middleware1_before',
            'middleware2_before',
            'handler',
            'middleware2_after',
            'middleware1_after',
        ], $executed->getArrayCopy());
        
        $this->assertEquals('result', $result);
    }

    public function testPipelineClear(): void
    {
        $pipeline = new MiddlewarePipeline();
        $pipeline->add(new LoggingMiddleware());
        $pipeline->add(new PerformanceMiddleware());
        
        $this->assertEquals(2, $pipeline->count());
        
        $pipeline->clear();
        
        $this->assertEquals(0, $pipeline->count());
    }

    public function testLoggingMiddleware(): void
    {
        $middleware = new LoggingMiddleware(false);
        
        $called = false;
        $next = function($player, $event, $data) use (&$called) {
            $called = true;
            return 'result';
        };
        
        $result = $middleware->handle($this->player, 'test_event', ['key' => 'value'], $next);
        
        $this->assertTrue($called);
        $this->assertEquals('result', $result);
    }

    public function testValidationMiddlewareWithValidData(): void
    {
        $middleware = new ValidationMiddleware();
        $middleware->addRule('test_event', [
            'name' => ['required' => true, 'type' => 'string', 'max_length' => 50],
            'age' => ['required' => false, 'type' => 'integer', 'min' => 0, 'max' => 120],
        ]);
        
        $next = function($player, $event, $data) {
            return 'success';
        };
        
        $result = $middleware->handle(
            $this->player, 
            'test_event', 
            ['name' => 'John', 'age' => 25], 
            $next
        );
        
        $this->assertEquals('success', $result);
    }

    public function testValidationMiddlewareWithMissingRequiredField(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Missing required field: name');
        
        $middleware = new ValidationMiddleware();
        $middleware->addRule('test_event', [
            'name' => ['required' => true, 'type' => 'string'],
        ]);
        
        $next = function($player, $event, $data) {
            return 'success';
        };
        
        $middleware->handle($this->player, 'test_event', ['age' => 25], $next);
    }

    public function testValidationMiddlewareWithInvalidType(): void
    {
        $this->expectException(ServerException::class);
        
        $middleware = new ValidationMiddleware();
        $middleware->addRule('test_event', [
            'age' => ['required' => true, 'type' => 'integer'],
        ]);
        
        $next = function($player, $event, $data) {
            return 'success';
        };
        
        $middleware->handle($this->player, 'test_event', ['age' => 'not_a_number'], $next);
    }

    public function testValidationMiddlewareWithExceedingMaxLength(): void
    {
        $this->expectException(ServerException::class);
        
        $middleware = new ValidationMiddleware();
        $middleware->addRule('test_event', [
            'name' => ['required' => true, 'type' => 'string', 'max_length' => 5],
        ]);
        
        $next = function($player, $event, $data) {
            return 'success';
        };
        
        $middleware->handle($this->player, 'test_event', ['name' => 'VeryLongName'], $next);
    }

    public function testPerformanceMiddleware(): void
    {
        $middleware = new PerformanceMiddleware(0.001); // 1ms 阈值
        
        $next = function($player, $event, $data) {
            usleep(2000); // 2ms 延迟
            return 'result';
        };
        
        $result = $middleware->handle($this->player, 'test_event', [], $next);
        
        $this->assertEquals('result', $result);
        
        // 验证统计信息
        $stats = $middleware->getStats();
        $this->assertArrayHasKey('test_event', $stats);
        $this->assertEquals(1, $stats['test_event']['count']);
        $this->assertGreaterThan(0, $stats['test_event']['avg_duration_ms']);
    }

    public function testPerformanceMiddlewareStats(): void
    {
        $middleware = new PerformanceMiddleware();
        
        $next = function($player, $event, $data) {
            return 'result';
        };
        
        // 执行多次
        for ($i = 0; $i < 5; $i++) {
            $middleware->handle($this->player, 'test_event', [], $next);
        }
        
        $stats = $middleware->getStats();
        
        $this->assertEquals(5, $stats['test_event']['count']);
        $this->assertEquals(5, $stats['test_event']['success_count']);
        $this->assertEquals(0, $stats['test_event']['error_count']);
        $this->assertEquals(100, $stats['test_event']['success_rate']);
    }

    public function testPerformanceMiddlewareResetStats(): void
    {
        $middleware = new PerformanceMiddleware();
        
        $next = function($player, $event, $data) {
            return 'result';
        };
        
        $middleware->handle($this->player, 'test_event', [], $next);
        
        $this->assertNotEmpty($middleware->getStats());
        
        $middleware->resetStats();
        
        $this->assertEmpty($middleware->getStats());
    }

    public function testPerformanceMiddlewareErrorHandling(): void
    {
        $middleware = new PerformanceMiddleware();
        
        $next = function($player, $event, $data) {
            throw new \RuntimeException('Test error');
        };
        
        $this->expectException(\RuntimeException::class);
        
        try {
            $middleware->handle($this->player, 'test_event', [], $next);
        } finally {
            $stats = $middleware->getStats();
            $this->assertEquals(1, $stats['test_event']['error_count']);
        }
    }
}

