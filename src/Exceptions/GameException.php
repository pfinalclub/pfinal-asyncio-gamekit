<?php

namespace PfinalClub\AsyncioGamekit\Exceptions;

use Exception;

/**
 * 游戏框架基础异常类
 */
class GameException extends Exception
{
    protected array $context = [];

    public function __construct(string $message = "", int $code = 0, ?Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * 获取异常上下文信息
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * 设置上下文信息
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        return [
            'type' => static::class,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context,
        ];
    }
}

