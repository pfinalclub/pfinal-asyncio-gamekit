<?php

namespace PfinalClub\AsyncioGamekit\Tests;

use PHPUnit\Framework\TestCase;
use PfinalClub\AsyncioGamekit\Container\{SimpleContainer, NotFoundException};

class ContainerTest extends TestCase
{
    private SimpleContainer $container;

    protected function setUp(): void
    {
        $this->container = new SimpleContainer();
    }

    public function testSetAndGetInstance(): void
    {
        $value = 'test_value';
        
        $this->container->set('test', $value);
        
        $this->assertTrue($this->container->has('test'));
        $this->assertEquals($value, $this->container->get('test'));
    }

    public function testSetAndGetCallable(): void
    {
        $this->container->set('test', function() {
            return 'test_value';
        });
        
        $result = $this->container->get('test');
        
        $this->assertEquals('test_value', $result);
    }

    public function testSingleton(): void
    {
        $counter = 0;
        
        $this->container->singleton('test', function() use (&$counter) {
            $counter++;
            return new \stdClass();
        });
        
        $instance1 = $this->container->get('test');
        $instance2 = $this->container->get('test');
        
        // 应该返回同一个实例
        $this->assertSame($instance1, $instance2);
        // 工厂函数只应该被调用一次
        $this->assertEquals(1, $counter);
    }

    public function testFactory(): void
    {
        $counter = 0;
        
        $this->container->factory('test', function() use (&$counter) {
            $counter++;
            return new \stdClass();
        });
        
        $instance1 = $this->container->get('test');
        $instance2 = $this->container->get('test');
        
        // 应该返回不同的实例
        $this->assertNotSame($instance1, $instance2);
        // 工厂函数应该被调用两次
        $this->assertEquals(2, $counter);
    }

    public function testGetNonexistentService(): void
    {
        $this->expectException(NotFoundException::class);
        
        $this->container->get('nonexistent');
    }

    public function testHas(): void
    {
        $this->assertFalse($this->container->has('test'));
        
        $this->container->set('test', 'value');
        
        $this->assertTrue($this->container->has('test'));
    }

    public function testRegisterServices(): void
    {
        $services = [
            'service1' => 'value1',
            'service2' => 'value2',
            'service3' => function() { return 'value3'; },
        ];
        
        $this->container->registerServices($services);
        
        $this->assertTrue($this->container->has('service1'));
        $this->assertTrue($this->container->has('service2'));
        $this->assertTrue($this->container->has('service3'));
        
        $this->assertEquals('value1', $this->container->get('service1'));
        $this->assertEquals('value2', $this->container->get('service2'));
        $this->assertEquals('value3', $this->container->get('service3'));
    }

    public function testRemove(): void
    {
        $this->container->set('test', 'value');
        
        $this->assertTrue($this->container->has('test'));
        
        $this->container->remove('test');
        
        $this->assertFalse($this->container->has('test'));
    }

    public function testClear(): void
    {
        $this->container->set('test1', 'value1');
        $this->container->set('test2', 'value2');
        
        $this->assertTrue($this->container->has('test1'));
        $this->assertTrue($this->container->has('test2'));
        
        $this->container->clear();
        
        $this->assertFalse($this->container->has('test1'));
        $this->assertFalse($this->container->has('test2'));
    }

    public function testGetServiceIds(): void
    {
        $this->container->set('service1', 'value1');
        $this->container->singleton('service2', fn() => 'value2');
        $this->container->factory('service3', fn() => 'value3');
        
        $ids = $this->container->getServiceIds();
        
        $this->assertContains('service1', $ids);
        $this->assertContains('service2', $ids);
        $this->assertContains('service3', $ids);
        $this->assertCount(3, $ids);
    }

    public function testSingletonOverwritesFactory(): void
    {
        $this->container->factory('test', fn() => 'factory');
        $this->container->singleton('test', fn() => 'singleton');
        
        $result = $this->container->get('test');
        
        $this->assertEquals('singleton', $result);
    }

    public function testFactoryOverwritesSingleton(): void
    {
        $this->container->singleton('test', fn() => 'singleton');
        $this->container->factory('test', fn() => 'factory');
        
        $result1 = $this->container->get('test');
        $result2 = $this->container->get('test');
        
        $this->assertEquals('factory', $result1);
        // 工厂应该每次创建新实例
        $this->assertEquals('factory', $result2);
    }

    public function testContainerPassedToFactory(): void
    {
        $this->container->set('dependency', 'dep_value');
        
        $this->container->singleton('service', function($container) {
            return $container->get('dependency');
        });
        
        $result = $this->container->get('service');
        
        $this->assertEquals('dep_value', $result);
    }
}

