<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Persistence;

use Redis;

/**
 * Redis 持久化适配器
 */
class RedisAdapter implements PersistenceAdapterInterface
{
    private Redis $redis;
    private string $prefix;

    /**
     * @param Redis $redis Redis实例
     * @param string $prefix 键前缀
     */
    public function __construct(Redis $redis, string $prefix = 'gamekit:')
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
    }

    /**
     * 静态工厂方法
     */
    public static function create(
        string $host = '127.0.0.1',
        int $port = 6379,
        string $password = '',
        int $database = 0,
        string $prefix = 'gamekit:'
    ): self {
        $redis = new Redis();
        $redis->connect($host, $port);
        
        if ($password) {
            $redis->auth($password);
        }
        
        if ($database > 0) {
            $redis->select($database);
        }

        return new self($redis, $prefix);
    }

    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        $key = $this->prefix . $key;
        $serialized = serialize($value);

        if ($ttl > 0) {
            return $this->redis->setex($key, $ttl, $serialized);
        }

        return $this->redis->set($key, $serialized);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->prefix . $key;
        $value = $this->redis->get($key);

        if ($value === false) {
            return $default;
        }

        return unserialize($value);
    }

    public function has(string $key): bool
    {
        $key = $this->prefix . $key;
        return $this->redis->exists($key) > 0;
    }

    public function delete(string $key): bool
    {
        $key = $this->prefix . $key;
        return $this->redis->del($key) > 0;
    }

    public function getMultiple(array $keys): array
    {
        $prefixedKeys = array_map(fn($k) => $this->prefix . $k, $keys);
        $values = $this->redis->mGet($prefixedKeys);

        $result = [];
        foreach ($keys as $i => $key) {
            $result[$key] = $values[$i] !== false ? unserialize($values[$i]) : null;
        }

        return $result;
    }

    public function setMultiple(array $values, int $ttl = 0): bool
    {
        $prefixedValues = [];
        foreach ($values as $key => $value) {
            $prefixedValues[$this->prefix . $key] = serialize($value);
        }

        $success = $this->redis->mSet($prefixedValues);

        // 如果设置了TTL，需要为每个键设置过期时间
        if ($success && $ttl > 0) {
            foreach (array_keys($prefixedValues) as $key) {
                $this->redis->expire($key, $ttl);
            }
        }

        return $success;
    }

    public function clear(): bool
    {
        $pattern = $this->prefix . '*';
        $keys = $this->redis->keys($pattern);

        if (empty($keys)) {
            return true;
        }

        return $this->redis->del($keys) > 0;
    }

    /**
     * 获取Redis实例
     */
    public function getRedis(): Redis
    {
        return $this->redis;
    }
}

