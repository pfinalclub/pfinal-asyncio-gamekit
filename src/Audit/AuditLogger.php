<?php

namespace PfinalClub\AsyncioGamekit\Audit;

use PfinalClub\AsyncioGamekit\Logger\LoggerFactory;

/**
 * 审计日志记录器
 * 记录所有关键操作用于安全审计
 */
class AuditLogger
{
    /** @var array 审计日志存储 */
    private array $logs = [];

    /** @var int 最大日志数量 */
    private int $maxLogs = 10000;

    /**
     * 记录审计日志
     */
    public function log(string $action, array $context = []): void
    {
        $log = [
            'action' => $action,
            'context' => $context,
            'timestamp' => microtime(true),
            'datetime' => date('Y-m-d H:i:s'),
            'user_id' => $context['user_id'] ?? 'system',
            'ip' => $context['ip'] ?? 'unknown',
        ];

        $this->logs[] = $log;

        // 限制日志数量
        if (count($this->logs) > $this->maxLogs) {
            array_shift($this->logs);
        }

        // 同时记录到普通日志
        LoggerFactory::info("Audit: {$action}", $context);
    }

    /**
     * 获取所有审计日志
     */
    public function getLogs(int $limit = 100, int $offset = 0): array
    {
        return array_slice($this->logs, $offset, $limit);
    }

    /**
     * 按操作类型筛选日志
     */
    public function getLogsByAction(string $action, int $limit = 100): array
    {
        return array_filter(
            $this->logs,
            fn($log) => $log['action'] === $action
        );
    }

    /**
     * 按用户ID筛选日志
     */
    public function getLogsByUser(string $userId, int $limit = 100): array
    {
        return array_filter(
            $this->logs,
            fn($log) => $log['user_id'] === $userId
        );
    }

    /**
     * 清空审计日志
     */
    public function clear(): void
    {
        $this->logs = [];
    }

    /**
     * 获取统计信息
     */
    public function getStats(): array
    {
        $actionCounts = [];
        foreach ($this->logs as $log) {
            $action = $log['action'];
            $actionCounts[$action] = ($actionCounts[$action] ?? 0) + 1;
        }

        return [
            'total_logs' => count($this->logs),
            'max_logs' => $this->maxLogs,
            'action_counts' => $actionCounts,
        ];
    }
}

