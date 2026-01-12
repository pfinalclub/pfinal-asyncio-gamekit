<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Room\Traits;

/**
 * DataStorage Trait
 * 负责房间自定义数据的存储管理
 */
trait DataStorage
{
    /** @var array 房间自定义数据 */
    protected array $data = [];

    /**
     * 设置自定义数据
     */
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
        
        // 清除缓存（数据已变化）
        $this->invalidateCache();
    }

    /**
     * 获取自定义数据
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * 检查数据是否存在
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * 删除数据
     */
    public function unset(string $key): void
    {
        unset($this->data[$key]);
        
        // 清除缓存（数据已变化）
        $this->invalidateCache();
    }

    /**
     * 删除数据（delete 是 unset 的别名）
     */
    public function delete(string $key): void
    {
        $this->unset($key);
    }

    /**
     * 获取所有数据
     */
    public function getAllData(): array
    {
        return $this->data;
    }

    /**
     * 清空所有数据
     */
    public function clearData(): void
    {
        $this->data = [];
        
        // 清除缓存（数据已变化）
        $this->invalidateCache();
    }
}

