<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Config;

/**
 * 配置接口
 */
interface ConfigInterface
{
    /**
     * 获取配置值
     * 
     * @param string $key 配置键
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;
    
    /**
     * 设置配置值
     * 
     * @param string $key 配置键
     * @param mixed $value 配置值
     * @return void
     */
    public function set(string $key, mixed $value): void;
    
    /**
     * 检查配置键是否存在
     * 
     * @param string $key 配置键
     * @return bool
     */
    public function has(string $key): bool;
    
    /**
     * 移除配置键
     * 
     * @param string $key 配置键
     * @return void
     */
    public function remove(string $key): void;
    
    /**
     * 加载配置
     * 
     * @param array $config 配置数组
     * @return void
     */
    public function load(array $config): void;
    
    /**
     * 获取所有配置
     * 
     * @return array
     */
    public function all(): array;
}