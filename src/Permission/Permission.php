<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Permission;

/**
 * 权限类
 */
class Permission
{
    // 玩家权限
    public const PLAYER_JOIN = 'player.join';
    public const PLAYER_LEAVE = 'player.leave';
    public const PLAYER_MESSAGE = 'player.message';
    
    // 房间权限
    public const ROOM_START = 'room.start';
    public const ROOM_PAUSE = 'room.pause';
    public const ROOM_STOP = 'room.stop';
    public const ROOM_KICK = 'room.kick';
    public const ROOM_SETTINGS = 'room.settings';
    
    // 管理权限
    public const ADMIN_ALL = 'admin.all';

    private string $name;
    private string $description;

    public function __construct(string $name, string $description = '')
    {
        $this->name = $name;
        $this->description = $description;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}

