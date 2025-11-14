<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Constants;

/**
 * 游戏事件常量
 * 
 * 定义所有系统和游戏事件的名称常量，避免魔法字符串
 */
class GameEvents
{
    // ==================== 系统事件 ====================
    
    /** 连接成功 */
    public const CONNECTED = 'connected';
    
    /** 设置名称 */
    public const SET_NAME = 'set_name';
    
    /** 名称已设置 */
    public const NAME_SET = 'name_set';
    
    /** 错误 */
    public const ERROR = 'error';
    
    // ==================== 房间事件 ====================
    
    /** 创建房间 */
    public const CREATE_ROOM = 'create_room';
    
    /** 房间已创建 */
    public const ROOM_CREATED = 'room_created';
    
    /** 加入房间 */
    public const JOIN_ROOM = 'join_room';
    
    /** 房间已加入 */
    public const ROOM_JOINED = 'room_joined';
    
    /** 离开房间 */
    public const LEAVE_ROOM = 'leave_room';
    
    /** 房间已离开 */
    public const ROOM_LEFT = 'room_left';
    
    /** 快速匹配 */
    public const QUICK_MATCH = 'quick_match';
    
    /** 匹配成功 */
    public const MATCHED = 'matched';
    
    /** 获取房间列表 */
    public const GET_ROOMS = 'get_rooms';
    
    /** 房间列表 */
    public const ROOMS_LIST = 'rooms_list';
    
    /** 获取统计信息 */
    public const GET_STATS = 'get_stats';
    
    /** 统计信息 */
    public const STATS = 'stats';
    
    // ==================== 玩家事件 ====================
    
    /** 玩家加入 */
    public const PLAYER_JOIN = 'player:join';
    
    /** 玩家离开 */
    public const PLAYER_LEAVE = 'player:leave';
    
    /** 玩家准备 */
    public const PLAYER_READY = 'ready';
    
    /** 玩家未准备 */
    public const PLAYER_UNREADY = 'unready';
    
    // ==================== 游戏事件 ====================
    
    /** 开始游戏 */
    public const START_GAME = 'start_game';
    
    /** 游戏已开始 */
    public const GAME_STARTED = 'game_started';
    
    /** 游戏动作 */
    public const GAME_ACTION = 'game_action';
    
    /** 游戏结束 */
    public const GAME_OVER = 'game_over';
    
    /** 游戏暂停 */
    public const GAME_PAUSE = 'game_pause';
    
    /** 游戏恢复 */
    public const GAME_RESUME = 'game_resume';
    
    // ==================== 批量消息 ====================
    
    /** 批量消息 */
    public const BATCH = 'batch';
    
    // ==================== 辅助方法 ====================
    
    /**
     * 获取所有系统事件
     * 
     * @return array
     */
    public static function getSystemEvents(): array
    {
        return [
            self::CONNECTED,
            self::SET_NAME,
            self::NAME_SET,
            self::ERROR,
        ];
    }
    
    /**
     * 获取所有房间事件
     * 
     * @return array
     */
    public static function getRoomEvents(): array
    {
        return [
            self::CREATE_ROOM,
            self::ROOM_CREATED,
            self::JOIN_ROOM,
            self::ROOM_JOINED,
            self::LEAVE_ROOM,
            self::ROOM_LEFT,
            self::QUICK_MATCH,
            self::MATCHED,
            self::GET_ROOMS,
            self::ROOMS_LIST,
            self::GET_STATS,
            self::STATS,
        ];
    }
    
    /**
     * 获取所有玩家事件
     * 
     * @return array
     */
    public static function getPlayerEvents(): array
    {
        return [
            self::PLAYER_JOIN,
            self::PLAYER_LEAVE,
            self::PLAYER_READY,
            self::PLAYER_UNREADY,
        ];
    }
    
    /**
     * 获取所有游戏事件
     * 
     * @return array
     */
    public static function getGameEvents(): array
    {
        return [
            self::START_GAME,
            self::GAME_STARTED,
            self::GAME_ACTION,
            self::GAME_OVER,
            self::GAME_PAUSE,
            self::GAME_RESUME,
        ];
    }
    
    /**
     * 获取所有允许的客户端事件（用于白名单验证）
     * 
     * @return array
     */
    public static function getAllowedClientEvents(): array
    {
        return [
            self::SET_NAME,
            self::CREATE_ROOM,
            self::JOIN_ROOM,
            self::LEAVE_ROOM,
            self::QUICK_MATCH,
            self::GET_ROOMS,
            self::GET_STATS,
            self::PLAYER_READY,
            self::PLAYER_UNREADY,
            self::START_GAME,
            self::GAME_ACTION,
        ];
    }
    
    /**
     * 检查事件是否为系统事件
     * 
     * @param string $event
     * @return bool
     */
    public static function isSystemEvent(string $event): bool
    {
        return in_array($event, self::getSystemEvents(), true);
    }
    
    /**
     * 检查事件是否为房间事件
     * 
     * @param string $event
     * @return bool
     */
    public static function isRoomEvent(string $event): bool
    {
        return in_array($event, self::getRoomEvents(), true);
    }
    
    /**
     * 检查事件是否为玩家事件
     * 
     * @param string $event
     * @return bool
     */
    public static function isPlayerEvent(string $event): bool
    {
        return in_array($event, self::getPlayerEvents(), true);
    }
    
    /**
     * 检查事件是否为游戏事件
     * 
     * @param string $event
     * @return bool
     */
    public static function isGameEvent(string $event): bool
    {
        return in_array($event, self::getGameEvents(), true);
    }
}

