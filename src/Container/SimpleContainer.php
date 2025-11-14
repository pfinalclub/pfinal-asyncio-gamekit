<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Container;

/**
 * 简单的依赖注入容器实现
 */
class SimpleContainer implements ContainerInterface
{
    /** @var array<string, mixed> 服务实例 */
    private array $instances = [];

    /** @var array<string, callable> 单例工厂 */
    private array $singletons = [];

    /** @var array<string, callable> 工厂函数 */
    private array $factories = [];

    /**
     * 从容器中获取服务
     */
    public function get(string $id): mixed
    {
        // 1. 检查是否已有实例
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // 2. 检查单例工厂
        if (isset($this->singletons[$id])) {
            $instance = ($this->singletons[$id])($this);
            $this->instances[$id] = $instance;
            return $instance;
        }

        // 3. 检查普通工厂
        if (isset($this->factories[$id])) {
            return ($this->factories[$id])($this);
        }

        // 4. 未找到
        throw NotFoundException::forService($id);
    }

    /**
     * 检查容器是否包含指定服务
     */
    public function has(string $id): bool
    {
        return isset($this->instances[$id]) 
            || isset($this->singletons[$id]) 
            || isset($this->factories[$id]);
    }

    /**
     * 设置服务
     */
    public function set(string $id, mixed $value): void
    {
        if (is_callable($value)) {
            $this->singleton($id, $value);
        } else {
            $this->instances[$id] = $value;
        }
    }

    /**
     * 注册单例服务
     */
    public function singleton(string $id, callable $factory): void
    {
        $this->singletons[$id] = $factory;
        // 清除可能存在的实例和工厂
        unset($this->instances[$id], $this->factories[$id]);
    }

    /**
     * 注册工厂服务
     */
    public function factory(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        // 清除可能存在的实例和单例
        unset($this->instances[$id], $this->singletons[$id]);
    }

    /**
     * 批量注册服务
     */
    public function registerServices(array $services): void
    {
        foreach ($services as $id => $value) {
            $this->set($id, $value);
        }
    }

    /**
     * 移除服务
     */
    public function remove(string $id): void
    {
        unset(
            $this->instances[$id],
            $this->singletons[$id],
            $this->factories[$id]
        );
    }

    /**
     * 清空所有服务
     */
    public function clear(): void
    {
        $this->instances = [];
        $this->singletons = [];
        $this->factories = [];
    }

    /**
     * 获取所有已注册的服务ID
     */
    public function getServiceIds(): array
    {
        return array_unique(array_merge(
            array_keys($this->instances),
            array_keys($this->singletons),
            array_keys($this->factories)
        ));
    }
}

