<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Memory;

/**
 * 内存管理器接口
 */
interface MemoryManagerInterface
{
    /**
     * 检查内存使用是否超限
     * 
     * @return bool true表示超限
     */
    public function isOverLimit(): bool;

    /**
     * 获取当前内存使用量（字节）
     * 
     * @return int
     */
    public function getCurrentUsage(): int;

    /**
     * 获取内存限制（字节）
     * 
     * @return int
     */
    public function getLimit(): int;

    /**
     * 获取内存使用百分比
     * 
     * @return float
     */
    public function getUsagePercentage(): float;

    /**
     * 强制垃圾回收
     * 
     * @return void
     */
    public function gc(): void;

    /**
     * 获取内存统计信息
     * 
     * @return array
     */
    public function getStats(): array;
}

