<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Container;

/**
 * 依赖注入容器接口（PSR-11 兼容）
 */
interface ContainerInterface
{
    /**
     * 从容器中获取服务
     * 
     * @param string $id 服务标识符
     * @return mixed 服务实例
     * @throws NotFoundException 服务未找到
     * @throws ContainerException 获取服务时发生错误
     */
    public function get(string $id): mixed;

    /**
     * 检查容器是否包含指定服务
     * 
     * @param string $id 服务标识符
     * @return bool
     */
    public function has(string $id): bool;

    /**
     * 设置服务（扩展 PSR-11）
     * 
     * @param string $id 服务标识符
     * @param mixed $value 服务实例或工厂函数
     * @return void
     */
    public function set(string $id, mixed $value): void;

    /**
     * 注册单例服务
     * 
     * @param string $id 服务标识符
     * @param callable $factory 工厂函数
     * @return void
     */
    public function singleton(string $id, callable $factory): void;

    /**
     * 注册工厂服务（每次调用都创建新实例）
     * 
     * @param string $id 服务标识符
     * @param callable $factory 工厂函数
     * @return void
     */
    public function factory(string $id, callable $factory): void;
}

