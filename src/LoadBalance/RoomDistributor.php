<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\LoadBalance;

use PfinalClub\AsyncioGamekit\Persistence\PersistenceAdapterInterface;

/**
 * 房间分布器
 * 负责在多进程间分配房间
 */
class RoomDistributor
{
    private LoadBalancerInterface $balancer;
    private ?PersistenceAdapterInterface $storage;
    private array $workers = [];
    private string $prefix = 'room:worker:';

    public function __construct(
        LoadBalancerInterface $balancer,
        ?PersistenceAdapterInterface $storage = null
    ) {
        $this->balancer = $balancer;
        $this->storage = $storage;
    }

    /**
     * 注册工作进程
     */
    public function registerWorker(int $workerId, array $info = []): void
    {
        $this->workers[$workerId] = array_merge([
            'id' => $workerId,
            'connections' => 0,
            'rooms' => 0,
            'memory' => 0,
        ], $info);
    }

    /**
     * 更新工作进程信息
     */
    public function updateWorkerInfo(int $workerId, array $info): void
    {
        if (isset($this->workers[$workerId])) {
            $this->workers[$workerId] = array_merge($this->workers[$workerId], $info);
        }
    }

    /**
     * 移除工作进程
     */
    public function unregisterWorker(int $workerId): void
    {
        unset($this->workers[$workerId]);
    }

    /**
     * 分配房间到工作进程
     */
    public function assignRoom(string $roomId, array $context = []): ?int
    {
        // 检查房间是否已经分配
        if ($this->storage) {
            $existing = $this->getRoomWorker($roomId);
            if ($existing !== null && isset($this->workers[$existing])) {
                return $existing;
            }
        }

        // 选择一个工作进程
        $workerId = $this->balancer->selectWorker($this->workers, $context);
        
        if ($workerId === null) {
            return null;
        }

        // 保存分配关系
        if ($this->storage) {
            $this->storage->set($this->prefix . $roomId, $workerId, 86400); // 24小时
        }

        // 更新工作进程房间数
        if (isset($this->workers[$workerId])) {
            $this->workers[$workerId]['rooms']++;
        }

        return $workerId;
    }

    /**
     * 获取房间所在的工作进程
     */
    public function getRoomWorker(string $roomId): ?int
    {
        if (!$this->storage) {
            return null;
        }

        $workerId = $this->storage->get($this->prefix . $roomId);
        return $workerId !== null ? (int)$workerId : null;
    }

    /**
     * 取消房间分配
     */
    public function unassignRoom(string $roomId): bool
    {
        if ($this->storage) {
            $workerId = $this->getRoomWorker($roomId);
            
            // 更新工作进程房间数
            if ($workerId !== null && isset($this->workers[$workerId])) {
                $this->workers[$workerId]['rooms'] = max(0, $this->workers[$workerId]['rooms'] - 1);
            }

            return $this->storage->delete($this->prefix . $roomId);
        }

        return true;
    }

    /**
     * 获取所有工作进程信息
     */
    public function getWorkers(): array
    {
        return $this->workers;
    }

    /**
     * 获取工作进程信息
     */
    public function getWorker(int $workerId): ?array
    {
        return $this->workers[$workerId] ?? null;
    }

    /**
     * 获取统计信息
     */
    public function getStats(): array
    {
        $totalRooms = 0;
        $totalConnections = 0;

        foreach ($this->workers as $worker) {
            $totalRooms += $worker['rooms'];
            $totalConnections += $worker['connections'];
        }

        return [
            'workers' => count($this->workers),
            'total_rooms' => $totalRooms,
            'total_connections' => $totalConnections,
            'workers_info' => $this->workers,
        ];
    }
}

