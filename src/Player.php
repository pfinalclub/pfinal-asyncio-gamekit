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
     * @param string $event 事件名称
     * @param mixed $data 数据
     */
    public function send(string $event, mixed $data = null): void
    {
        if ($this->connection && method_exists($this->connection, 'send')) {
            $message = json_encode([
                'event' => $event,
                'data' => $data,
                'timestamp' => microtime(true)
            ]);
            $this->connection->send($message);
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

