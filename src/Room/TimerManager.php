<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Room;

/**
 * 定时器管理实现类
 */
class TimerManager implements TimerManagerInterface
{
    /** @var OptimizedRoom 所属房间 */
    protected OptimizedRoom $room;
    
    /** @var array<int, int> 定时器列表 */
    protected array $timers = [];

    /**
     * @param OptimizedRoom $room 所属房间
     */
    public function __construct(OptimizedRoom $room)
    {
        $this->room = $room;
    }

    /**
     * 添加定时器
     * 
     * @param int $interval 间隔时间（毫秒）
     * @param callable $callback 回调函数
     * @param bool $persistent 是否持久化
     * @param array $args 回调参数
     * @return int 定时器ID
     */
    public function addTimer(int $interval, callable $callback, bool $persistent = false, array $args = []): int
    {
        // 将回调绑定到房间实例
        $boundCallback = $callback->bindTo($this->room);
        
        // 使用 Workerman 的定时器
        $timerId = \Workerman\Timer::add(
            $interval / 1000, // Workerman 使用秒
            $boundCallback,
            $args,
            $persistent
        );

        $this->timers[$timerId] = $timerId;
        return $timerId;
    }

    /**
     * 移除定时器
     */
    public function removeTimer(int $timerId): bool
    {
        if (isset($this->timers[$timerId])) {
            \Workerman\Timer::del($this->timers[$timerId]);
            unset($this->timers[$timerId]);
            return true;
        }
        return false;
    }

    /**
     * 清除所有定时器
     */
    public function clearAllTimers(): void
    {
        foreach ($this->timers as $timerId) {
            \Workerman\Timer::del($timerId);
        }
        $this->timers = [];
    }
}