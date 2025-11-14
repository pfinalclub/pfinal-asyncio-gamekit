<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Config;

use PfinalClub\AsyncioGamekit\Exceptions\ConfigException;

/**
 * Configuration management class
 * 统一的配置管理系统，支持多来源配置加载和访问
 */
class Configuration implements ConfigInterface
{
    /** @var array 配置数据 */
    protected array $config = [];

    /**
     * 构造函数
     *
     * @param array $config 初始配置数据
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
    
    /**
     * {@inheritDoc}
     */
    public function remove(string $key): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;
        
        foreach ($keys as $index => $k) {
            if ($index === count($keys) - 1) {
                unset($config[$k]);
                return;
            }
            
            if (!isset($config[$k]) || !is_array($config[$k])) {
                return;
            }
            
            $config = &$config[$k];
        }
    }
    
    /**
     * {@inheritDoc}
     */
    public function load(array $config): void
    {
        $this->config = array_merge_recursive($this->config, $config);
    }
    
    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * 加载配置文件
     *
     * @param string $filePath 配置文件路径
     * @return self
     */
    public static function loadFromFile(string $filePath): self
    {
        if (!file_exists($filePath)) {
            throw ConfigException::fileNotFound($filePath);
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $config = [];

        switch ($extension) {
            case 'php':
                $config = require $filePath;
                break;
            case 'json':
                $content = file_get_contents($filePath);
                $config = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                break;
            // 可以扩展支持更多格式（yaml, ini等）
        }

        if (!is_array($config)) {
            throw ConfigException::invalidFormat($filePath, 'Expected array structure');
        }

        return new self($config);
    }

    /**
     * 获取配置值
     *
     * @param string $key 配置键，支持点分隔符（如：database.host）
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * 设置配置值
     *
     * @param string $key 配置键
     * @param mixed $value 配置值
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $index => $k) {
            if ($index === count($keys) - 1) {
                $config[$k] = $value;
            } else {
                if (!isset($config[$k]) || !is_array($config[$k])) {
                    $config[$k] = [];
                }
                $config = &$config[$k];
            }
        }
    }

    /**
     * 检查配置键是否存在
     *
     * @param string $key 配置键
     * @return bool
     */
    public function has(string $key): bool
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                return false;
            }
            $value = $value[$k];
        }

        return true;
    }

    /**
     * 获取所有配置数据
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->config;
    }

    /**
     * 合并配置
     *
     * @param array $config 要合并的配置
     * @param bool $overwrite 是否覆盖已存在的配置
     * @return self
     */
    public function merge(array $config, bool $overwrite = true): self
    {
        if ($overwrite) {
            $this->config = array_merge_recursive($this->config, $config);
        } else {
            $this->config = array_replace_recursive($config, $this->config);
        }

        return $this;
    }
}