<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Persistence;

/**
 * 内存持久化适配器（用于测试和临时存储）
 */
class MemoryAdapter implements PersistenceAdapterInterface
{
    private array $data = [];
    private array $expires = [];

    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        $this->data[$key] = $value;
        
        if ($ttl > 0) {
            $this->expires[$key] = time() + $ttl;
        } else {
            unset($this->expires[$key]);
        }

        return true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        // 检查是否过期
        if (isset($this->expires[$key]) && time() > $this->expires[$key]) {
            $this->delete($key);
            return $default;
        }

        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        // 检查是否过期
        if (isset($this->expires[$key]) && time() > $this->expires[$key]) {
            $this->delete($key);
            return false;
        }

        return array_key_exists($key, $this->data);
    }

    public function delete(string $key): bool
    {
        unset($this->data[$key], $this->expires[$key]);
        return true;
    }

    public function getMultiple(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    public function setMultiple(array $values, int $ttl = 0): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function clear(): bool
    {
        $this->data = [];
        $this->expires = [];
        return true;
    }

    /**
     * 获取所有数据（用于调试）
     */
    public function getAll(): array
    {
        return $this->data;
    }
}

