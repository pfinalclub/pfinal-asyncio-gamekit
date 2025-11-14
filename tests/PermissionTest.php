<?php

namespace PfinalClub\AsyncioGamekit\Tests;

use PHPUnit\Framework\TestCase;
use PfinalClub\AsyncioGamekit\Permission\{Permission, Role, PermissionManager};
use PfinalClub\AsyncioGamekit\Player;

class PermissionTest extends TestCase
{
    private PermissionManager $manager;
    private Player $player;

    protected function setUp(): void
    {
        $this->manager = new PermissionManager();
        $this->player = new Player('test_player', null, 'TestPlayer');
    }

    public function testPermissionConstants(): void
    {
        $this->assertEquals('player.join', Permission::PLAYER_JOIN);
        $this->assertEquals('player.leave', Permission::PLAYER_LEAVE);
        $this->assertEquals('player.message', Permission::PLAYER_MESSAGE);
        
        $this->assertEquals('room.start', Permission::ROOM_START);
        $this->assertEquals('room.kick', Permission::ROOM_KICK);
        
        $this->assertEquals('admin.all', Permission::ADMIN_ALL);
    }

    public function testRoleConstants(): void
    {
        $this->assertEquals('player', Role::PLAYER);
        $this->assertEquals('moderator', Role::MODERATOR);
        $this->assertEquals('admin', Role::ADMIN);
        $this->assertEquals('owner', Role::OWNER);
    }

    public function testCreatePermission(): void
    {
        $permission = new Permission('test.permission', 'Test permission description');
        
        $this->assertEquals('test.permission', $permission->getName());
        $this->assertEquals('Test permission description', $permission->getDescription());
    }

    public function testCreateRole(): void
    {
        $permissions = [Permission::PLAYER_JOIN, Permission::PLAYER_LEAVE];
        $role = new Role('test_role', $permissions, 'Test role');
        
        $this->assertEquals('test_role', $role->getName());
        $this->assertEquals($permissions, $role->getPermissions());
        $this->assertEquals('Test role', $role->getDescription());
    }

    public function testRoleAddPermission(): void
    {
        $role = new Role('test_role');
        
        $role->addPermission(Permission::PLAYER_JOIN);
        
        $this->assertContains(Permission::PLAYER_JOIN, $role->getPermissions());
    }

    public function testRoleAddDuplicatePermission(): void
    {
        $role = new Role('test_role', [Permission::PLAYER_JOIN]);
        
        $role->addPermission(Permission::PLAYER_JOIN);
        
        // 不应该重复添加
        $permissions = $role->getPermissions();
        $this->assertEquals(1, count($permissions));
    }

    public function testRoleRemovePermission(): void
    {
        $role = new Role('test_role', [Permission::PLAYER_JOIN, Permission::PLAYER_LEAVE]);
        
        $role->removePermission(Permission::PLAYER_JOIN);
        
        $this->assertNotContains(Permission::PLAYER_JOIN, $role->getPermissions());
        $this->assertContains(Permission::PLAYER_LEAVE, $role->getPermissions());
    }

    public function testRoleHasPermission(): void
    {
        $role = new Role('test_role', [Permission::PLAYER_JOIN]);
        
        $this->assertTrue($role->hasPermission(Permission::PLAYER_JOIN));
        $this->assertFalse($role->hasPermission(Permission::ROOM_START));
    }

    public function testAdminHasAllPermissions(): void
    {
        $role = new Role('admin', [Permission::ADMIN_ALL]);
        
        // 管理员应该有所有权限
        $this->assertTrue($role->hasPermission(Permission::PLAYER_JOIN));
        $this->assertTrue($role->hasPermission(Permission::ROOM_START));
        $this->assertTrue($role->hasPermission(Permission::ROOM_KICK));
        $this->assertTrue($role->hasPermission('any.permission'));
    }

    public function testDefaultRoles(): void
    {
        $roles = Role::createDefaultRoles();
        
        $this->assertArrayHasKey(Role::PLAYER, $roles);
        $this->assertArrayHasKey(Role::MODERATOR, $roles);
        $this->assertArrayHasKey(Role::ADMIN, $roles);
        $this->assertArrayHasKey(Role::OWNER, $roles);
        
        // 验证角色实例
        $this->assertInstanceOf(Role::class, $roles[Role::PLAYER]);
        $this->assertInstanceOf(Role::class, $roles[Role::MODERATOR]);
    }

    public function testPlayerRolePermissions(): void
    {
        $roles = Role::createDefaultRoles();
        $playerRole = $roles[Role::PLAYER];
        
        $this->assertTrue($playerRole->hasPermission(Permission::PLAYER_JOIN));
        $this->assertTrue($playerRole->hasPermission(Permission::PLAYER_LEAVE));
        $this->assertTrue($playerRole->hasPermission(Permission::PLAYER_MESSAGE));
        
        $this->assertFalse($playerRole->hasPermission(Permission::ROOM_KICK));
    }

    public function testModeratorRolePermissions(): void
    {
        $roles = Role::createDefaultRoles();
        $moderatorRole = $roles[Role::MODERATOR];
        
        $this->assertTrue($moderatorRole->hasPermission(Permission::PLAYER_JOIN));
        $this->assertTrue($moderatorRole->hasPermission(Permission::ROOM_KICK));
        $this->assertTrue($moderatorRole->hasPermission(Permission::ROOM_PAUSE));
    }

    public function testPermissionManagerRegisterRole(): void
    {
        $role = new Role('custom_role', [Permission::PLAYER_JOIN]);
        
        $this->manager->registerRole($role);
        
        $retrievedRole = $this->manager->getRole('custom_role');
        $this->assertSame($role, $retrievedRole);
    }

    public function testPermissionManagerGetNonexistentRole(): void
    {
        $role = $this->manager->getRole('nonexistent');
        
        $this->assertNull($role);
    }

    public function testPermissionManagerAssignRole(): void
    {
        $result = $this->manager->assignRole($this->player, Role::MODERATOR);
        
        $this->assertTrue($result);
        
        $role = $this->manager->getPlayerRole($this->player);
        $this->assertEquals(Role::MODERATOR, $role->getName());
    }

    public function testPermissionManagerAssignNonexistentRole(): void
    {
        $result = $this->manager->assignRole($this->player, 'nonexistent_role');
        
        $this->assertFalse($result);
    }

    public function testPermissionManagerGetPlayerRoleDefault(): void
    {
        // 没有分配角色时，应该返回默认的 PLAYER 角色
        $role = $this->manager->getPlayerRole($this->player);
        
        $this->assertEquals(Role::PLAYER, $role->getName());
    }

    public function testPermissionManagerHasPermission(): void
    {
        $this->manager->assignRole($this->player, Role::MODERATOR);
        
        $this->assertTrue($this->manager->hasPermission($this->player, Permission::PLAYER_JOIN));
        $this->assertTrue($this->manager->hasPermission($this->player, Permission::ROOM_KICK));
        $this->assertFalse($this->manager->hasPermission($this->player, Permission::ROOM_SETTINGS));
    }

    public function testPermissionManagerRequirePermissionSuccess(): void
    {
        $this->manager->assignRole($this->player, Role::MODERATOR);
        
        // 不应该抛出异常
        $this->manager->requirePermission($this->player, Permission::ROOM_KICK);
        
        $this->assertTrue(true); // 如果到这里说明没有抛出异常
    }

    public function testPermissionManagerRequirePermissionFail(): void
    {
        $this->expectException(\PfinalClub\AsyncioGamekit\Exceptions\PermissionException::class);
        $this->expectExceptionMessage('does not have permission');
        
        $this->manager->assignRole($this->player, Role::PLAYER);
        
        $this->manager->requirePermission($this->player, Permission::ROOM_KICK);
    }

    public function testPermissionManagerRevokeRole(): void
    {
        $this->manager->assignRole($this->player, Role::MODERATOR);
        
        $this->manager->revokeRole($this->player);
        
        // 应该回到默认的 PLAYER 角色
        $role = $this->manager->getPlayerRole($this->player);
        $this->assertEquals(Role::PLAYER, $role->getName());
    }

    public function testPermissionManagerGetAllRoles(): void
    {
        $roles = $this->manager->getAllRoles();
        
        // 应该包含默认角色
        $this->assertGreaterThanOrEqual(4, count($roles));
        $this->assertArrayHasKey(Role::PLAYER, $roles);
        $this->assertArrayHasKey(Role::ADMIN, $roles);
    }

    public function testPermissionManagerGetPlayerRoles(): void
    {
        $player1 = new Player('player1');
        $player2 = new Player('player2');
        
        $this->manager->assignRole($player1, Role::MODERATOR);
        $this->manager->assignRole($player2, Role::ADMIN);
        
        $playerRoles = $this->manager->getPlayerRoles();
        
        $this->assertArrayHasKey('player1', $playerRoles);
        $this->assertArrayHasKey('player2', $playerRoles);
        $this->assertEquals(Role::MODERATOR, $playerRoles['player1']);
        $this->assertEquals(Role::ADMIN, $playerRoles['player2']);
    }

    public function testAdminRoleInManager(): void
    {
        $this->manager->assignRole($this->player, Role::ADMIN);
        
        // 管理员应该有所有权限
        $this->assertTrue($this->manager->hasPermission($this->player, Permission::PLAYER_JOIN));
        $this->assertTrue($this->manager->hasPermission($this->player, Permission::ROOM_KICK));
        $this->assertTrue($this->manager->hasPermission($this->player, Permission::ROOM_START));
        $this->assertTrue($this->manager->hasPermission($this->player, 'any.custom.permission'));
    }
}

