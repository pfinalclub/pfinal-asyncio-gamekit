<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Security;

use PfinalClub\AsyncioGamekit\Constants\GameConfig;

/**
 * 连接数限制器
 * 防止单个IP创建过多连接
 */
class ConnectionLimiter
{
    /** @var array<string, int> IP地址到连接数的映射 */
    private array $connections = [];
    
    /** @var int 单IP最大连接数 */
    private int $maxConnectionsPerIp;
    
    /**
     * @param int|null $maxConnectionsPerIp 单IP最大连接数，null使用默认值
     */
    public function __construct(?int $maxConnectionsPerIp = null)
    {
        $this->maxConnectionsPerIp = $maxConnectionsPerIp ?? GameConfig::MAX_CONNECTIONS_PER_IP;
    }
    
    /**
     * 检查IP是否可以建立新连接
     */
    public function canConnect(string $ip): bool
    {
        $count = $this->connections[$ip] ?? 0;
        return $count < $this->maxConnectionsPerIp;
    }
    
    /**
     * 添加连接
     * 
     * @return bool 是否添加成功
     */
    public function addConnection(string $ip): bool
    {
        if (!$this->canConnect($ip)) {
            return false;
        }
        
        $this->connections[$ip] = ($this->connections[$ip] ?? 0) + 1;
        return true;
    }
    
    /**
     * 移除连接
     */
    public function removeConnection(string $ip): void
    {
        if (!isset($this->connections[$ip])) {
            return;
        }
        
        $this->connections[$ip]--;
        
        // 清理计数为0的记录
        if ($this->connections[$ip] <= 0) {
            unset($this->connections[$ip]);
        }
    }
    
    /**
     * 获取指定IP的连接数
     */
    public function getConnectionCount(string $ip): int
    {
        return $this->connections[$ip] ?? 0;
    }
    
    /**
     * 获取所有IP的连接统计
     */
    public function getStats(): array
    {
        return [
            'total_ips' => count($this->connections),
            'total_connections' => array_sum($this->connections),
            'max_per_ip' => $this->maxConnectionsPerIp,
            'top_ips' => $this->getTopIps(10),
        ];
    }
    
    /**
     * 获取连接数最多的IP列表
     */
    private function getTopIps(int $limit): array
    {
        $sorted = $this->connections;
        arsort($sorted);
        return array_slice($sorted, 0, $limit, true);
    }
    
    /**
     * 清空所有连接记录
     */
    public function reset(): void
    {
        $this->connections = [];
    }
    
    /**
     * 屏蔽IP（设置为最大值）
     */
    public function blockIp(string $ip): void
    {
        $this->connections[$ip] = $this->maxConnectionsPerIp;
    }
    
    /**
     * 解除IP屏蔽
     */
    public function unblockIp(string $ip): void
    {
        unset($this->connections[$ip]);
    }
}

