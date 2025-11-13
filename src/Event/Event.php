<?php

namespace PfinalClub\AsyncioGamekit\Event;

/**
 * 事件基类
 */
class Event
{
    protected string $name;
    protected mixed $data;
    protected float $timestamp;
    protected bool $propagationStopped = false;

    public function __construct(string $name, mixed $data = null)
    {
        $this->name = $name;
        $this->data = $data;
        $this->timestamp = microtime(true);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getTimestamp(): float
    {
        return $this->timestamp;
    }

    /**
     * 停止事件传播
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    /**
     * 检查事件传播是否已停止
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}

