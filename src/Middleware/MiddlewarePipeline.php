<?php

namespace PfinalClub\AsyncioGamekit\Middleware;

use PfinalClub\AsyncioGamekit\Player;

/**
 * 中间件管道
 * 
 * 负责按顺序执行中间件链
 */
class MiddlewarePipeline
{
    /** @var array<MiddlewareInterface> 中间件列表 */
    private array $middlewares = [];

    /**
     * 添加中间件
     */
    public function add(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * 批量添加中间件
     * 
     * @param array<MiddlewareInterface> $middlewares
     */
    public function addMultiple(array $middlewares): self
    {
        foreach ($middlewares as $middleware) {
            if ($middleware instanceof MiddlewareInterface) {
                $this->add($middleware);
            }
        }
        return $this;
    }

    /**
     * 通过管道处理消息
     * 
     * @param Player $player 玩家对象
     * @param string $event 事件名称
     * @param mixed $data 消息数据
     * @param callable $finalHandler 最终处理器
     * @return mixed 处理结果
     */
    public function process(Player $player, string $event, mixed $data, callable $finalHandler): mixed
    {
        // 构建中间件链
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            function ($next, $middleware) {
                return function ($player, $event, $data) use ($middleware, $next) {
                    return $middleware->handle($player, $event, $data, $next);
                };
            },
            $finalHandler
        );

        // 执行管道
        return $pipeline($player, $event, $data);
    }

    /**
     * 获取所有中间件
     * 
     * @return array<MiddlewareInterface>
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * 清空所有中间件
     */
    public function clear(): void
    {
        $this->middlewares = [];
    }

    /**
     * 获取中间件数量
     */
    public function count(): int
    {
        return count($this->middlewares);
    }
}

