<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Constants;

/**
 * 游戏配置常量
 * 集中管理所有魔法数字
 */
class GameConfig
{
    // ============ 房间配置 ============
    
    /** 默认最大玩家数 */
    public const ROOM_MAX_PLAYERS_DEFAULT = 4;
    
    /** 默认最小玩家数 */
    public const ROOM_MIN_PLAYERS_DEFAULT = 2;
    
    /** 空房间销毁延迟（秒） */
    public const ROOM_EMPTY_DESTROY_DELAY = 5;
    
    /** 房间清理间隔（秒） */
    public const ROOM_CLEANUP_INTERVAL = 300;
    
    /** 最大房间数限制 */
    public const MAX_ROOMS_DEFAULT = 1000;
    
    // ============ 玩家配置 ============
    
    /** 玩家名称最大长度 */
    public const PLAYER_NAME_MAX_LENGTH = 32;
    
    /** 玩家名称最小长度 */
    public const PLAYER_NAME_MIN_LENGTH = 1;
    
    // ============ 限流配置 ============
    
    /** 默认令牌桶容量 */
    public const RATE_LIMIT_CAPACITY_DEFAULT = 20;
    
    /** 默认令牌生成速率（每秒） */
    public const RATE_LIMIT_RATE_DEFAULT = 20;
    
    /** 最大令牌桶数量 */
    public const RATE_LIMIT_MAX_BUCKETS = 10000;
    
    // ============ 内存配置 ============
    
    /** 默认内存限制（MB） */
    public const MEMORY_LIMIT_MB_DEFAULT = 256;
    
    /** 内存警告阈值（百分比） */
    public const MEMORY_WARNING_THRESHOLD = 0.8;
    
    // ============ 服务器配置 ============
    
    /** 默认服务器端口 */
    public const SERVER_PORT_DEFAULT = 2345;
    
    /** 默认服务器地址 */
    public const SERVER_HOST_DEFAULT = '0.0.0.0';
    
    /** 单IP最大连接数 */
    public const MAX_CONNECTIONS_PER_IP = 10;
    
    // ============ 消息配置 ============
    
    /** 最大消息大小（字节） */
    public const MAX_MESSAGE_SIZE = 65536; // 64KB
    
    /** 最大消息嵌套层级 */
    public const MAX_MESSAGE_NEST_LEVEL = 5;
    
    // ============ 广播配置 ============
    
    /** 广播队列刷新间隔（毫秒） */
    public const BROADCAST_FLUSH_INTERVAL_MS = 50;
    
    /** 广播队列最大消息数 */
    public const BROADCAST_MAX_QUEUE_SIZE = 100;
    
    // ============ 定时器配置 ============
    
    /** 定时器延迟微秒 */
    public const TIMER_DELAY_MICROSECONDS = 50000; // 50ms
    
    // ============ 审计日志配置 ============
    
    /** 审计日志最大缓冲大小 */
    public const AUDIT_LOG_MAX_BUFFER = 1000;
    
    /** 审计日志刷新间隔（秒） */
    public const AUDIT_LOG_FLUSH_INTERVAL = 60;
    
    // ============ 事件总线配置 ============
    
    /** 事件队列最大长度 */
    public const EVENT_QUEUE_MAX_SIZE = 10000;
    
    // ============ 连接池配置 ============
    
    /** 连接池最小大小 */
    public const CONNECTION_POOL_MIN_SIZE = 10;
    
    /** 连接池最大大小 */
    public const CONNECTION_POOL_MAX_SIZE = 100;
    
    // ============ 对象池配置 ============
    
    /** 对象池最大大小 */
    public const OBJECT_POOL_MAX_SIZE = 1000;
    
    // ============ 性能监控配置 ============
    
    /** 慢请求阈值（秒） */
    public const SLOW_REQUEST_THRESHOLD = 1.0;
    
    /** 性能采样率（0-1） */
    public const PERFORMANCE_SAMPLING_RATE = 0.1;
    
    /**
     * 获取房间默认配置
     */
    public static function getRoomDefaults(): array
    {
        return [
            'max_players' => self::ROOM_MAX_PLAYERS_DEFAULT,
            'min_players' => self::ROOM_MIN_PLAYERS_DEFAULT,
            'auto_start' => false,
        ];
    }
    
    /**
     * 获取限流默认配置
     */
    public static function getRateLimitDefaults(): array
    {
        return [
            'capacity' => self::RATE_LIMIT_CAPACITY_DEFAULT,
            'rate' => self::RATE_LIMIT_RATE_DEFAULT,
            'max_buckets' => self::RATE_LIMIT_MAX_BUCKETS,
        ];
    }
    
    /**
     * 获取内存默认配置
     */
    public static function getMemoryDefaults(): array
    {
        return [
            'limit_mb' => self::MEMORY_LIMIT_MB_DEFAULT,
            'warning_threshold' => self::MEMORY_WARNING_THRESHOLD,
        ];
    }
    
    /**
     * 获取服务器默认配置
     */
    public static function getServerDefaults(): array
    {
        return [
            'host' => self::SERVER_HOST_DEFAULT,
            'port' => self::SERVER_PORT_DEFAULT,
            'max_connections_per_ip' => self::MAX_CONNECTIONS_PER_IP,
        ];
    }
}

