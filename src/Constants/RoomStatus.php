<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Constants;

/**
 * 房间状态常量
 * 消除魔法字符串，提高代码可维护性
 */
class RoomStatus
{
    /** 等待中 */
    public const WAITING = 'waiting';
    
    /** 运行中 */
    public const RUNNING = 'running';
    
    /** 已结束 */
    public const FINISHED = 'finished';

    /** 所有有效状态 */
    private const VALID_STATUSES = [
        self::WAITING,
        self::RUNNING,
        self::FINISHED,
    ];

    /**
     * 检查状态是否有效
     * 
     * @param string $status 状态值
     * @return bool 是否有效
     */
    public static function isValid(string $status): bool
    {
        return in_array($status, self::VALID_STATUSES, true);
    }

    /**
     * 获取所有有效状态
     * 
     * @return array<string> 状态数组
     */
    public static function all(): array
    {
        return self::VALID_STATUSES;
    }
}
