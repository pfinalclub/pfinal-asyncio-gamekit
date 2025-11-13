<?php

namespace PfinalClub\AsyncioGamekit;

/**
 * Player 玩家类
 * 封装玩家通信和状态管理
 */
class Player
{
    /** @var string 玩家ID */
    private string $id;
    
    /** @var string|null 玩家名称 */
    private ?string $name;
    
    /** @var mixed 连接对象（如 Workerman Connection） */
    private mixed $connection;
    
    /** @var Room|null 所在房间 */
    private ?Room $room = null;
    
    /** @var array 玩家自定义数据 */
    private array $data = [];
    
    /** @var bool 是否准备 */
    private bool $ready = false;
    
    /** @var bool|null 连接是否有效（缓存验证结果） */
    private ?bool $hasValidConnection = null;

    /**
     * @param string $id 玩家ID
     * @param mixed $connection 连接对象
     * @param string|null $name 玩家名称
     */
    public function __construct(string $id, mixed $connection = null, ?string $name = null)
    {
        $this->id = $id;
        $this->connection = $connection;
        $this->name = $name ?? "Player_{$id}";
    }

    /**
     * 获取玩家ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * 获取玩家名称
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 设置玩家名称
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * 获取连接对象
     */
    public function getConnection(): mixed
    {
        return $this->connection;
    }

    /**
     * 发送消息给玩家
     * 
     * @param string $event 事件名称（或预编码的完整消息）
     * @param mixed $data 数据
     * @param bool $preEncoded 是否已预编码（用于广播优化）
     * @return bool 发送是否成功
     */
    public function send(string $event, mixed $data = null, bool $preEncoded = false): bool
    {
        // 延迟验证连接（只验证一次）
        if ($this->hasValidConnection === null) {
            $this->hasValidConnection = $this->connection && method_exists($this->connection, 'send');
        }
        
        if (!$this->hasValidConnection) {
            return false;
        }
        
        try {
            if ($preEncoded) {
                // 如果已预编码，直接发送（$event 包含完整消息）
                $message = $event;
            } else {
                $message = json_encode([
                    'event' => $event,
                    'data' => $data,
                    'timestamp' => microtime(true)
                ], JSON_THROW_ON_ERROR);
            }
            
            $result = $this->connection->send($message);
            
            // 如果发送失败，标记连接无效
            if ($result === false) {
                $this->hasValidConnection = false;
            }
            
            return $result !== false;
        } catch (\JsonException $e) {
            // JSON 编码失败
            error_log("Failed to encode message for player {$this->id}: {$e->getMessage()}");
            return false;
        } catch (\Throwable $e) {
            // 发送失败（连接可能已断开）
            $this->hasValidConnection = false;
            return false;
        }
    }

    /**
     * 设置所在房间
     */
    public function setRoom(?Room $room): void
    {
        $this->room = $room;
    }

    /**
     * 获取所在房间
     */
    public function getRoom(): ?Room
    {
        return $this->room;
    }

    /**
     * 设置自定义数据
     */
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * 获取自定义数据
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * 检查是否有指定数据
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * 获取所有自定义数据
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * 设置准备状态
     */
    public function setReady(bool $ready): void
    {
        $this->ready = $ready;
    }

    /**
     * 是否已准备
     */
    public function isReady(): bool
    {
        return $this->ready;
    }

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'ready' => $this->ready,
            'data' => $this->data
        ];
    }
}

