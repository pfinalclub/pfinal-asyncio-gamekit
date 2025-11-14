<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Event;

/**
 * 事件监听器接口
 */
interface EventListenerInterface
{
    /**
     * 处理事件
     * 
     * @param Event $event 事件对象
     * @return void
     */
    public function handle(Event $event): void;
}

