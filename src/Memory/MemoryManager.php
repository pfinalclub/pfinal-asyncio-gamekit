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

        $usage = $this->getCurrentUsage();
        $percentage = $usage / $this->limit;

        // 发出警告
        if ($percentage >= $this->warningThreshold && !$this->warningIssued) {
            $this->issueWarning($usage, $percentage);
            $this->warningIssued = true;
        }

        // 如果低于警告阈值，重置警告标志
        if ($percentage < $this->warningThreshold) {
            $this->warningIssued = false;
        }

        return $usage >= $this->limit;
    }

    /**
     * 获取当前内存使用量
     */
    public function getCurrentUsage(): int
    {
        return memory_get_usage(true);
    }

    /**
     * 获取内存限制
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

        return ($this->getCurrentUsage() / $this->limit) * 100;
    }

    /**
     * 强制垃圾回收
     */
    public function gc(): void
    {
        $before = $this->getCurrentUsage();
        
        // PHP 垃圾回收
        gc_collect_cycles();
        
        $after = $this->getCurrentUsage();
        $freed = $before - $after;

        if ($freed > 0) {
            LoggerFactory::debug("Garbage collection freed memory", [
                'freed_bytes' => $freed,
                'freed_mb' => round($freed / 1024 / 1024, 2),
                'before_mb' => round($before / 1024 / 1024, 2),
                'after_mb' => round($after / 1024 / 1024, 2),
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

        if ($this->isOverLimit()) {
            LoggerFactory::error("Memory limit exceeded", $this->getStats());
            
            // 尝试垃圾回收
            $this->gc();
            
            // 如果仍超限，触发紧急清理
            if ($this->isOverLimit()) {
                $this->emergencyCleanup();
            }
        }
    }

    /**
     * 获取内存统计信息
     */
    public function getStats(): array
    {
        $current = $this->getCurrentUsage();
        $peak = memory_get_peak_usage(true);

        return [
            'current_bytes' => $current,
            'current_mb' => round($current / 1024 / 1024, 2),
            'peak_bytes' => $peak,
            'peak_mb' => round($peak / 1024 / 1024, 2),
            'limit_bytes' => $this->limit,
            'limit_mb' => round($this->limit / 1024 / 1024, 2),
            'usage_percentage' => round($this->getUsagePercentage(), 2),
            'php_memory_limit' => ini_get('memory_limit'),
        ];
    }

    /**
     * 发出内存警告
     */
    private function issueWarning(int $usage, float $percentage): void
    {
        LoggerFactory::warning("Memory usage high", [
            'usage_mb' => round($usage / 1024 / 1024, 2),
            'limit_mb' => round($this->limit / 1024 / 1024, 2),
            'percentage' => round($percentage * 100, 2) . '%',
            'threshold' => round($this->warningThreshold * 100, 2) . '%',
        ]);
    }

    /**
     * 紧急清理（子类可重写以实现具体清理逻辑）
     */
    protected function emergencyCleanup(): void
    {
        LoggerFactory::critical("Emergency memory cleanup triggered", $this->getStats());
        
        // 再次尝试垃圾回收
        $this->gc();
    }

    /**
     * 设置检查间隔
     */
    public function setCheckInterval(int $seconds): void
    {
        $this->checkInterval = max(1, $seconds);
    }

    /**
     * 设置告警阈值
     */
    public function setWarningThreshold(float $threshold): void
    {
        $this->warningThreshold = max(0.0, min(1.0, $threshold));
    }
}

