<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Middleware;

use PfinalClub\AsyncioGamekit\Player;

/**
 * 中间件接口
 * 
 * 中间件可以在消息处理前后执行自定义逻辑
 */
interface MiddlewareInterface
{
    /**
     * 处理消息
     * 
     * @param Player $player 玩家对象
     * @param string $event 事件名称
     * @param mixed $data 消息数据
     * @param callable $next 下一个中间件或最终处理器
     * @return mixed 处理结果
     */
    public function handle(Player $player, string $event, mixed $data, callable $next): mixed;
}

