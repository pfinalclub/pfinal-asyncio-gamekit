<?php

namespace PfinalClub\AsyncioGamekit\Permission;

/**
 * 角色类
 */
class Role
{
    // 预定义角色
    public const PLAYER = 'player';
    public const MODERATOR = 'moderator';
    public const ADMIN = 'admin';
    public const OWNER = 'owner';

    private string $name;
    private array $permissions = [];
    private string $description;

    public function __construct(string $name, array $permissions = [], string $description = '')
    {
        $this->name = $name;
        $this->permissions = $permissions;
        $this->description = $description;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function addPermission(string $permission): void
    {
        if (!in_array($permission, $this->permissions, true)) {
            $this->permissions[] = $permission;
        }
    }

    public function removePermission(string $permission): void
    {
        $this->permissions = array_filter(
            $this->permissions, 
            fn($p) => $p !== $permission
        );
    }

    public function hasPermission(string $permission): bool
    {
        // 管理员拥有所有权限
        if (in_array(Permission::ADMIN_ALL, $this->permissions, true)) {
            return true;
        }

        return in_array($permission, $this->permissions, true);
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * 创建默认角色
     */
    public static function createDefaultRoles(): array
    {
        return [
            self::PLAYER => new self(self::PLAYER, [
                Permission::PLAYER_JOIN,
                Permission::PLAYER_LEAVE,
                Permission::PLAYER_MESSAGE,
            ], 'Basic player role'),
            
            self::MODERATOR => new self(self::MODERATOR, [
                Permission::PLAYER_JOIN,
                Permission::PLAYER_LEAVE,
                Permission::PLAYER_MESSAGE,
                Permission::ROOM_KICK,
                Permission::ROOM_PAUSE,
            ], 'Moderator with kick and pause abilities'),
            
            self::ADMIN => new self(self::ADMIN, [
                Permission::ADMIN_ALL,
            ], 'Administrator with all permissions'),
            
            self::OWNER => new self(self::OWNER, [
                Permission::ADMIN_ALL,
            ], 'Room owner with all permissions'),
        ];
    }
}

