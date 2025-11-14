<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Room;

/**
 * 定时器管理接口
 */
interface TimerManagerInterface
{
    /**
     * 添加定时器
     * @param int $interval 间隔时间（毫秒）
     * @param callable $callback 回调函数
     * @param bool $persistent 是否持久化
     * @param array $args 回调参数
     * @return int 定时器ID
     */
    public function addTimer(int $interval, callable $callback, bool $persistent = false, array $args = []): int;
    
    /**
     * 移除定时器
     * @param int $timerId 定时器ID
     * @return bool
     */
    public function removeTimer(int $timerId): bool;
    
    /**
     * 清除所有定时器
     * @return void
     */
    public function clearAllTimers(): void;
}