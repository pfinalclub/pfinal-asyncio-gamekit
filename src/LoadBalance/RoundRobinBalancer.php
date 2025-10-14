<?php

namespace PfinalClub\AsyncioGamekit\LoadBalance;

/**
 * 轮询负载均衡器
 */
class RoundRobinBalancer implements LoadBalancerInterface
{
    private int $currentIndex = 0;

    public function selectWorker(array $workers, array $context = []): ?int
    {
        if (empty($workers)) {
            return null;
        }

        $workerIds = array_keys($workers);
        $selected = $workerIds[$this->currentIndex % count($workerIds)];
        
        $this->currentIndex++;
        
        return $selected;
    }
}

