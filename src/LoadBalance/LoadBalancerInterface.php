<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\LoadBalance;

/**
 * 负载均衡器接口
 */
interface LoadBalancerInterface
{
    /**
     * 选择一个工作进程
     * 
     * @param array $workers 可用的工作进程列表
     * @param array $context 上下文信息
     * @return int|null 工作进程ID，null表示没有可用进程
     */
    public function selectWorker(array $workers, array $context = []): ?int;
}

