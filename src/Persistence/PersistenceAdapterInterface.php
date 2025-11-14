<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Persistence;

/**
 * 持久化适配器接口
 */
interface PersistenceAdapterInterface
{
    /**
     * 保存数据
     * 
     * @param string $key 键
     * @param mixed $value 值
     * @param int $ttl 过期时间（秒），0表示永不过期
     * @return bool 是否成功
     */
    public function set(string $key, mixed $value, int $ttl = 0): bool;

    /**
     * 获取数据
     * 
     * @param string $key 键
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * 检查键是否存在
     * 
     * @param string $key 键
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * 删除数据
     * 
     * @param string $key 键
     * @return bool 是否成功
     */
    public function delete(string $key): bool;

    /**
     * 批量获取数据
     * 
     * @param array $keys 键数组
     * @return array
     */
    public function getMultiple(array $keys): array;

    /**
     * 批量设置数据
     * 
     * @param array $values 键值对数组
     * @param int $ttl 过期时间（秒）
     * @return bool 是否成功
     */
    public function setMultiple(array $values, int $ttl = 0): bool;

    /**
     * 清空所有数据
     * 
     * @return bool 是否成功
     */
    public function clear(): bool;
}

