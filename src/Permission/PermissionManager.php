<?php

namespace PfinalClub\AsyncioGamekit\Permission;

use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\Room\Room;

/**
 * 权限管理器
 */
class PermissionManager
{
    /** @var array<string, Role> 角色映射 */
    private array $roles = [];

    /** @var array<string, string> 玩家ID到角色的映射 */
    private array $playerRoles = [];

    public function __construct()
    {
        // 注册默认角色
        foreach (Role::createDefaultRoles() as $roleName => $role) {
            $this->registerRole($role);
        }
    }

    /**
     * 注册角色
     */
    public function registerRole(Role $role): void
    {
        $this->roles[$role->getName()] = $role;
    }

    /**
     * 获取角色
     */
    public function getRole(string $roleName): ?Role
    {
        return $this->roles[$roleName] ?? null;
    }

    /**
     * 为玩家分配角色
     */
    public function assignRole(Player $player, string $roleName): bool
    {
        if (!isset($this->roles[$roleName])) {
            return false;
        }

        $this->playerRoles[$player->getId()] = $roleName;
        return true;
    }

    /**
     * 获取玩家角色
     */
    public function getPlayerRole(Player $player): ?Role
    {
        $roleName = $this->playerRoles[$player->getId()] ?? Role::PLAYER;
        return $this->getRole($roleName);
    }

    /**
     * 检查玩家是否有指定权限
     */
    public function hasPermission(Player $player, string $permission): bool
    {
        $role = $this->getPlayerRole($player);
        
        if (!$role) {
            return false;
        }

        return $role->hasPermission($permission);
    }

    /**
     * 确保玩家有权限，否则抛出异常
     * 
     * @throws \RuntimeException
     */
    public function requirePermission(Player $player, string $permission): void
    {
        if (!$this->hasPermission($player, $permission)) {
            throw new \RuntimeException(
                "Player {$player->getName()} does not have permission: {$permission}"
            );
        }
    }

    /**
     * 移除玩家角色
     */
    public function revokeRole(Player $player): void
    {
        unset($this->playerRoles[$player->getId()]);
    }

    /**
     * 获取所有角色
     */
    public function getAllRoles(): array
    {
        return $this->roles;
    }

    /**
     * 获取所有玩家角色映射
     */
    public function getPlayerRoles(): array
    {
        return $this->playerRoles;
    }
}

