<?php

namespace PfinalClub\AsyncioGamekit\Tests\Helpers;

/**
 * 模拟连接对象，用于测试
 */
class MockConnection
{
    public string $id;
    public array $sentMessages = [];

    public function __construct(?string $id = null)
    {
        $this->id = $id ?? uniqid('conn_');
    }

    /**
     * 模拟发送消息
     */
    public function send(string $data): void
    {
        $this->sentMessages[] = $data;
    }

    /**
     * 获取最后发送的消息
     */
    public function getLastMessage(): ?array
    {
        if (empty($this->sentMessages)) {
            return null;
        }
        
        $lastMessage = end($this->sentMessages);
        return json_decode($lastMessage, true);
    }

    /**
     * 获取所有发送的消息
     */
    public function getAllMessages(): array
    {
        return array_map(
            fn($msg) => json_decode($msg, true),
            $this->sentMessages
        );
    }

    /**
     * 清空消息历史
     */
    public function clearMessages(): void
    {
        $this->sentMessages = [];
    }
}

