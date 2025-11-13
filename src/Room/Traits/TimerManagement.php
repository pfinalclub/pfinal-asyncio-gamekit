<?php

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
     * 添加定时任务
     * 
     * @param float $interval 时间间隔（秒）
     * @param callable $callback 回调函数
     * @param bool $repeat 是否重复执行
     * @return int|null 定时器ID，失败返回null
     */
    protected function addTimer(float $interval, callable $callback, bool $repeat = false): ?int
    {
        $loop = get_event_loop();
        $timerId = $loop->addTimer($interval, $callback, $repeat);
        
        if ($timerId !== null) {
            $this->timerIds[] = $timerId;
        }
        
        return $timerId;
    }

    /**
     * 删除定时任务
     */
    protected function removeTimer(int $timerId): void
    {
        $loop = get_event_loop();
        $loop->delTimer($timerId);
        $this->timerIds = array_filter($this->timerIds, fn($id) => $id !== $timerId);
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

