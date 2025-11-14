<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Event;

/**
 * 事件总线接口
 */
interface EventBusInterface
{
    /**
     * 订阅事件
     * 
     * @param string $eventName 事件名称
     * @param callable|EventListenerInterface $listener 监听器
     * @param int $priority 优先级（数字越大优先级越高）
     * @return string 监听器ID
     */
    public function subscribe(string $eventName, callable|EventListenerInterface $listener, int $priority = 0): string;

    /**
     * 取消订阅
     * 
     * @param string $listenerId 监听器ID
     * @return bool 是否成功取消
     */
    public function unsubscribe(string $listenerId): bool;

    /**
     * 发布事件
     * 
     * @param string|Event $event 事件名称或事件对象
     * @param mixed $data 事件数据（当第一个参数是字符串时使用）
     * @return Event 事件对象
     */
    public function publish(string|Event $event, mixed $data = null): Event;

    /**
     * 异步发布事件
     * 
     * @param string|Event $event 事件名称或事件对象
     * @param mixed $data 事件数据（当第一个参数是字符串时使用）
     * @return void
     */
    public function publishAsync(string|Event $event, mixed $data = null): void;

    /**
     * 获取事件的所有监听器
     * 
     * @param string $eventName 事件名称
     * @return array
     */
    public function getListeners(string $eventName): array;

    /**
     * 清除事件的所有监听器
     * 
     * @param string $eventName 事件名称
     * @return void
     */
    public function clear(string $eventName): void;
}

