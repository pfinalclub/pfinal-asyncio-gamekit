<?php

namespace PfinalClub\AsyncioGamekit\Persistence;

/**
 * 文件持久化适配器
 */
class FileAdapter implements PersistenceAdapterInterface
{
    private string $directory;
    private string $extension = '.dat';

    public function __construct(string $directory = 'storage/cache')
    {
        $this->directory = rtrim($directory, '/');
        
        // 确保目录存在
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }
    }

    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        $file = $this->getFilePath($key);
        
        $data = [
            'value' => $value,
            'expire_at' => $ttl > 0 ? time() + $ttl : 0,
        ];

        $serialized = serialize($data);
        $result = file_put_contents($file, $serialized, LOCK_EX);

        return $result !== false;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $contents = file_get_contents($file);
        if ($contents === false) {
            return $default;
        }

        $data = unserialize($contents);
        
        // 检查是否过期
        if ($data['expire_at'] > 0 && time() > $data['expire_at']) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    public function has(string $key): bool
    {
        $file = $this->getFilePath($key);
        
        if (!file_exists($file)) {
            return false;
        }

        $contents = file_get_contents($file);
        if ($contents === false) {
            return false;
        }

        $data = unserialize($contents);
        
        // 检查是否过期
        if ($data['expire_at'] > 0 && time() > $data['expire_at']) {
            $this->delete($key);
            return false;
        }

        return true;
    }

    public function delete(string $key): bool
    {
        $file = $this->getFilePath($key);
        
        if (file_exists($file)) {
            return unlink($file);
        }

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
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }
        return $success;
    }

    public function clear(): bool
    {
        $files = glob($this->directory . '/*' . $this->extension);
        
        if ($files === false) {
            return false;
        }

        $success = true;
        foreach ($files as $file) {
            if (!unlink($file)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * 获取文件路径
     */
    private function getFilePath(string $key): string
    {
        $hash = md5($key);
        return $this->directory . '/' . $hash . $this->extension;
    }
}

