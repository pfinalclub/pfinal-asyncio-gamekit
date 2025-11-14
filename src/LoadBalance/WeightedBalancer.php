<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\LoadBalance;

/**
 * 加权负载均衡器
 */
class WeightedBalancer implements LoadBalancerInterface
{
    private array $weights = [];
    private int $currentIndex = 0;
    private int $currentWeight = 0;
    private int $maxWeight = 0;
    private int $gcdWeight = 0;

    /**
     * @param array $weights 权重配置 [workerId => weight]
     */
    public function __construct(array $weights = [])
    {
        $this->setWeights($weights);
    }

    /**
     * 设置权重
     */
    public function setWeights(array $weights): void
    {
        $this->weights = $weights;
        
        if (!empty($weights)) {
            $this->maxWeight = max($weights);
            $this->gcdWeight = $this->calculateGCD(array_values($weights));
        }
    }

    public function selectWorker(array $workers, array $context = []): ?int
    {
        if (empty($workers)) {
            return null;
        }

        $workerIds = array_keys($workers);
        
        // 如果没有设置权重，使用轮询
        if (empty($this->weights)) {
            return $workerIds[$this->currentIndex++ % count($workerIds)];
        }

        while (true) {
            $this->currentIndex = ($this->currentIndex + 1) % count($workerIds);
            
            if ($this->currentIndex === 0) {
                $this->currentWeight = $this->currentWeight - $this->gcdWeight;
                if ($this->currentWeight <= 0) {
                    $this->currentWeight = $this->maxWeight;
                }
            }

            $workerId = $workerIds[$this->currentIndex];
            $weight = $this->weights[$workerId] ?? 1;

            if ($weight >= $this->currentWeight) {
                return $workerId;
            }
        }
    }

    /**
     * 计算最大公约数
     */
    private function calculateGCD(array $numbers): int
    {
        if (count($numbers) === 1) {
            return $numbers[0];
        }

        $gcd = $numbers[0];
        for ($i = 1; $i < count($numbers); $i++) {
            $gcd = $this->gcd($gcd, $numbers[$i]);
        }

        return $gcd;
    }

    /**
     * 求两个数的最大公约数
     */
    private function gcd(int $a, int $b): int
    {
        return $b === 0 ? $a : $this->gcd($b, $a % $b);
    }
}

