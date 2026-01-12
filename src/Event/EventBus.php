<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Event;

use PfinalClub\AsyncioGamekit\Logger\LoggerFactory;
use function PfinalClub\Asyncio\create_task;

/**
 * 事件总线实现
 * 
 * 支持同步和异步事件发布、优先级排序、事件传播控制
 * 
 * 性能优化：使用延迟排序策略，仅在发布事件时按需排序
 */
class EventBus implements EventBusInterface
{
    /** @var array<string, array> 事件监听器映射 */
    private array $listeners = [];

    /** @var array<string, array> 监听器ID到事件名称的映射 */
    private array $listenerIds = [];

    /** @var array<string, bool> 标记哪些事件的监听器需要重新排序 */
    private array $needsSort = [];

    /**
     * 订阅事件
     */
    public function subscribe(string $eventName, callable|EventListenerInterface $listener, int $priority = 0): string
    {
        $listenerId = $this->generateListenerId();

        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }

        $this->listeners[$eventName][$listenerId] = [
            'listener' => $listener,
            'priority' => $priority,
        ];

        $this->listenerIds[$listenerId] = $eventName;

        // 标记为需要排序（延迟到 publish 时执行）
        $this->needsSort[$eventName] = true;

        return $listenerId;
    }

    /**
     * 取消订阅
     */
    public function unsubscribe(string $listenerId): bool
    {
        if (!isset($this->listenerIds[$listenerId])) {
            return false;
        }

        $eventName = $this->listenerIds[$listenerId];
        unset($this->listeners[$eventName][$listenerId]);
        unset($this->listenerIds[$listenerId]);

        // 如果该事件没有监听器了，清除事件
        if (empty($this->listeners[$eventName])) {
            unset($this->listeners[$eventName]);
        }

        return true;
    }

    /**
     * 发布事件（同步）
     */
    public function publish(string|Event $event, mixed $data = null): Event
    {
        $eventObject = $this->ensureEventObject($event, $data);
        $eventName = $eventObject->getName();

        if (!isset($this->listeners[$eventName])) {
            return $eventObject;
        }

        // 延迟排序：仅在需要时排序
        $this->sortListenersIfNeeded($eventName);

        foreach ($this->listeners[$eventName] as $listenerId => $listenerData) {
            if ($eventObject->isPropagationStopped()) {
                break;
            }

            try {
                $listener = $listenerData['listener'];
                
                if ($listener instanceof EventListenerInterface) {
                    $listener->handle($eventObject);
                } elseif (is_callable($listener)) {
                    $listener($eventObject);
                }
            } catch (\Throwable $e) {
                LoggerFactory::error("Error in event listener", [
                    'event' => $eventName,
                    'listener_id' => $listenerId,
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                // 继续处理其他监听器，不中断事件传播
                // 但可以选择性地停止事件传播（根据错误严重程度）
                if ($e instanceof \Error) {
                    // 严重错误，停止事件传播
                    $eventObject->stopPropagation();
                }
            }
        }

        return $eventObject;
    }

    /**
     * 发布事件（异步）
     */
    public function publishAsync(string|Event $event, mixed $data = null): void
    {
        create_task(fn() => $this->publish($event, $data));
    }

    /**
     * 获取事件的所有监听器
     */
    public function getListeners(string $eventName): array
    {
        return $this->listeners[$eventName] ?? [];
    }

    /**
     * 清除事件的所有监听器
     */
    public function clear(string $eventName): void
    {
        if (isset($this->listeners[$eventName])) {
            // 移除所有监听器ID映射
            foreach (array_keys($this->listeners[$eventName]) as $listenerId) {
                unset($this->listenerIds[$listenerId]);
            }
            
            unset($this->listeners[$eventName]);
            unset($this->needsSort[$eventName]);
        }
    }

    /**
     * 清除所有监听器
     */
    public function clearAll(): void
    {
        $this->listeners = [];
        $this->listenerIds = [];
        $this->needsSort = [];
    }

    /**
     * 获取统计信息
     */
    public function getStats(): array
    {
        $eventCount = count($this->listeners);
        $listenerCount = count($this->listenerIds);
        
        $detailedStats = [];
        foreach ($this->listeners as $eventName => $listeners) {
            $detailedStats[$eventName] = count($listeners);
        }

        return [
            'total_events' => $eventCount,
            'total_listeners' => $listenerCount,
            'events' => $detailedStats,
        ];
    }

    /**
     * 确保输入是事件对象
     */
    private function ensureEventObject(string|Event $event, mixed $data = null): Event
    {
        if ($event instanceof Event) {
            return $event;
        }

        return new Event($event, $data);
    }

    /**
     * 生成唯一的监听器ID
     */
    private function generateListenerId(): string
    {
        return 'listener_' . uniqid('', true);
    }

    /**
     * 按需排序监听器（延迟排序优化）
     */
    private function sortListenersIfNeeded(string $eventName): void
    {
        if (!isset($this->needsSort[$eventName]) || !$this->needsSort[$eventName]) {
            return;
        }

        uasort($this->listeners[$eventName], static function($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });

        $this->needsSort[$eventName] = false;
    }
}

