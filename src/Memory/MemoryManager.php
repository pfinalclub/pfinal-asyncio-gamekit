<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Memory;

use PfinalClub\AsyncioGamekit\Logger\LoggerFactory;

/**
 * 内存管理器实现
 * 
 * 功能：
 * - 监控内存使用
 * - 自动垃圾回收
 * - 内存超限告警
 */
class MemoryManager implements MemoryManagerInterface
{
    /** @var int 内存限制（字节） */
    private int $limit;

    /** @var float 告警阈值（0-1） */
    private float $warningThreshold;

    /** @var float 上次检查时间 */
    private float $lastCheckTime = 0;

    /** @var int 检查间隔（秒） */
    private int $checkInterval = 5;

    /** @var bool 是否已发出警告 */
    private bool $warningIssued = false;

    /**
     * @param int $limit 内存限制（MB），0表示不限制
     * @param float $warningThreshold 告警阈值（0-1）
     */
    public function __construct(int $limit = 256, float $warningThreshold = 0.8)
    {
        $this->limit = $limit * 1024 * 1024; // 转换为字节
        $this->warningThreshold = max(0.0, min(1.0, $warningThreshold));
    }

    /**
     * 检查内存使用是否超限
     */
    public function isOverLimit(): bool
    {
        if ($this->limit === 0) {
            return false; // 不限制
        }

        $current = memory_get_usage(true);
        return $current > $this->limit;
    }

    /**
     * 获取当前内存使用量（字节）
     */
    public function getCurrentUsage(): int
    {
        return memory_get_usage(true);
    }

    /**
     * 获取内存限制（字节）
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * 获取内存使用百分比
     */
    public function getUsagePercentage(): float
    {
        if ($this->limit === 0) {
            return 0.0;
        }

        return (memory_get_usage(true) / $this->limit) * 100;
    }

    /**
     * 获取内存使用百分比
     */
    public function getUsagePercent(): float
    {
        if ($this->limit === 0) {
            return 0.0;
        }

        return (memory_get_usage(true) / $this->limit) * 100;
    }

    /**
     * 获取统计信息
     */
    public function getStats(): array
    {
        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);

        $stats = [
            'current' => $current,
            'peak' => $peak,
            'limit' => $this->limit,
            'usage_percent' => $this->limit > 0 ? ($current / $this->limit) * 100 : 0,
            'peak_percent' => $this->limit > 0 ? ($peak / $this->limit) * 100 : 0,
            'warning_threshold_percent' => $this->warningThreshold * 100,
        ];

        // 转换为可读格式
        $stats['current_mb'] = round($current / 1024 / 1024, 2);
        $stats['peak_mb'] = round($peak / 1024 / 1024, 2);
        $stats['limit_mb'] = round($this->limit / 1024 / 1024, 2);

        return $stats;
    }

    /**
     * 手动触发垃圾回收
     */
    public function gc(): void
    {
        $before = memory_get_usage(true);
        gc_collect_cycles();
        $after = memory_get_usage(true);

        $freed = $before - $after;
        if ($freed > 0) {
            LoggerFactory::info("Garbage collection freed {size}", [
                'size' => $this->formatBytes($freed),
            ]);
        }
    }

    /**
     * 定期检查（应在主循环中调用）
     */
    public function periodicCheck(): void
    {
        $now = microtime(true);
        
        if ($now - $this->lastCheckTime < $this->checkInterval) {
            return;
        }

        $this->lastCheckTime = $now;
        $stats = $this->getStats();

        // 检查是否超限
        if ($this->isOverLimit()) {
            LoggerFactory::critical("Memory limit exceeded: {current}/{limit}", [
                'current' => $stats['current_mb'],
                'limit' => $stats['limit_mb'],
                'usage_percent' => round($stats['usage_percent'], 2),
            ]);
            
            // 触发垃圾回收
            $this->gc();
        }
        // 检查是否需要警告
        elseif ($stats['usage_percent'] > ($this->warningThreshold * 100) && !$this->warningIssued) {
            $this->warningIssued = true;
            LoggerFactory::warning("Memory usage high: {current}/{limit} ({percent}%)", [
                'current' => $stats['current_mb'],
                'limit' => $stats['limit_mb'],
                'percent' => round($stats['usage_percent'], 2),
                'threshold' => round($this->warningThreshold * 100, 2),
            ]);
        }
        // 重置警告状态
        elseif ($stats['usage_percent'] <= ($this->warningThreshold * 100)) {
            $this->warningIssued = false;
        }

        LoggerFactory::debug("Memory check", [
            'current' => $stats['current_mb'],
            'limit' => $stats['limit_mb'],
            'usage_percent' => round($stats['usage_percent'], 2),
        ]);
    }

    /**
     * 格式化字节数
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}