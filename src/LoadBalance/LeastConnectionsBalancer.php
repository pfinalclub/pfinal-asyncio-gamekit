<?php

namespace PfinalClub\AsyncioGamekit\LoadBalance;

/**
 * 最少连接数负载均衡器
 */
class LeastConnectionsBalancer implements LoadBalancerInterface
{
    public function selectWorker(array $workers, array $context = []): ?int
    {
        if (empty($workers)) {
            return null;
        }

        // 找到连接数最少的工作进程
        $minConnections = PHP_INT_MAX;
        $selectedWorker = null;

        foreach ($workers as $workerId => $workerInfo) {
            $connections = $workerInfo['connections'] ?? 0;
            
            if ($connections < $minConnections) {
                $minConnections = $connections;
                $selectedWorker = $workerId;
            }
        }

        return $selectedWorker;
    }
}

