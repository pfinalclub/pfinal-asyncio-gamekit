<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Room\Traits;

use function PfinalClub\Asyncio\{sleep, get_event_loop};

/**
 * TimerManagement Trait
 * 负责房间内定时器的管理功能
 */
trait TimerManagement
{
    /** @var array 定时任务ID列表 */
    protected array $timerIds = [];

    /**
     * 添加定时器
     * 
     * @param float $interval 间隔时间（秒）
     * @param callable $callback 回调函数
     * @param bool $persistent 是否持久化（重复执行）
     * @return int 定时器ID
     */
    public function addTimer(float $interval, callable $callback, bool $persistent = false): int
    {
        $loop = get_event_loop();
        $timerId = $loop->addTimer($interval, $callback, $persistent);
        
        if ($timerId !== null) {
            $this->timerIds[] = $timerId;
            return $timerId;
        }
        
        return 0;
    }

    /**
     * 移除定时器
     * 
     * @param int $timerId 定时器ID
     * @return bool 是否成功
     */
    public function removeTimer(int $timerId): bool
    {
        $loop = get_event_loop();
        $loop->delTimer($timerId);
        
        $beforeCount = count($this->timerIds);
        $this->timerIds = array_filter($this->timerIds, fn($id) => $id !== $timerId);
        
        return count($this->timerIds) < $beforeCount;
    }

    /**
     * 延迟执行任务
     */
    protected function delay(float $seconds): mixed
    {
        sleep($seconds);
        
        return null;
    }

    /**
     * 清除所有定时器
     */
    protected function clearAllTimers(): void
    {
        $loop = get_event_loop();
        foreach ($this->timerIds as $timerId) {
            try {
                $loop->delTimer($timerId);
            } catch (\Throwable $e) {
                // 忽略已删除的定时器错误
            }
        }
        $this->timerIds = [];
    }
}
