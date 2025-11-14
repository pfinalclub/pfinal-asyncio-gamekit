<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Config;

/**
 * 配置管理类
 */
class Config implements ConfigInterface
{
    /**
     * @var array 配置数据
     */
    protected array $config = [];
    
    /**
     * @param array $config 初始配置
     */
    public function __construct(array $config = [])
    {
        $this->load($config);
    }
    
    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (is_array($value) && array_key_exists($k, $value)) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }
        
        return $value;
    }
    
    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;
        
        foreach ($keys as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }
        
        $config = $value;
    }
    
    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (is_array($value) && array_key_exists($k, $value)) {
                $value = $value[$k];
            } else {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function remove(string $key): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;
        $lastKey = array_pop($keys);
        
        foreach ($keys as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                return;
            }
            $config = &$config[$k];
        }
        
        unset($config[$lastKey]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function load(array $config): void
    {
        $this->config = array_merge_recursive($this->config, $config);
    }
    
    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->config;
    }
}