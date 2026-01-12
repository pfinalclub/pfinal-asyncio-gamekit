<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Tests;

use PHPUnit\Framework\TestCase;

/**
 * v3.0 升级验证测试
 * 验证升级后的核心功能正常工作
 */
class V3UpgradeTest extends TestCase
{
    public function testBasicRoomCompatibility(): void
    {
        // 测试基础的 Room 类仍然可用
        $room = new class('test_room') extends \PfinalClub\AsyncioGamekit\Room {
            protected function run(): mixed
            {
                return 'test_result';
            }
        };
        
        $this->assertEquals('test_room', $room->getId());
        $this->assertEquals('waiting', $room->getStatus());
    }
    
    public function testBasicPlayerCompatibility(): void
    {
        // 测试基础的 Player 类仍然可用
        $player = new \PfinalClub\AsyncioGamekit\Player('test_player', null, 'TestPlayer');
        
        $this->assertEquals('test_player', $player->getId());
        $this->assertEquals('TestPlayer', $player->getName());
        $this->assertFalse($player->isReady());
        
        // 测试数据存储
        $player->set('score', 100);
        $this->assertEquals(100, $player->get('score'));
        $this->assertTrue($player->has('score'));
    }
    
    public function testBasicRoomManagerCompatibility(): void
    {
        // 测试基础的 RoomManager 类仍然可用
        $roomManager = new \PfinalClub\AsyncioGamekit\RoomManager();
        
        // 获取统计
        $stats = $roomManager->getStats();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_rooms', $stats);
        $this->assertArrayHasKey('total_players', $stats);
    }
    
    public function testExamplesExist(): void
    {
        // 测试示例文件存在
        $this->assertTrue(file_exists(__DIR__ . '/../examples/SimpleGame.php'));
        $this->assertTrue(file_exists(__DIR__ . '/../examples/CardGame.php'));
        $this->assertTrue(file_exists(__DIR__ . '/../examples/V30FeaturesDemo.php'));
    }
    
    public function testNoBreakingChanges(): void
    {
        // 验证没有破坏性变更
        $room = new class('no_breaking_test') extends \PfinalClub\AsyncioGamekit\Room {
            protected function run(): mixed
            {
                return 'no_breaking_result';
            }
        };
        
        // 所有旧的方法仍然可用
        $this->assertTrue(method_exists($room, 'addPlayer'));
        $this->assertTrue(method_exists($room, 'removePlayer'));
        $this->assertTrue(method_exists($room, 'broadcast'));
        $this->assertTrue(method_exists($room, 'getPlayer'));
        $this->assertTrue(method_exists($room, 'getPlayers'));
        $this->assertTrue(method_exists($room, 'start'));
        $this->assertTrue(method_exists($room, 'getStatus'));
        $this->assertTrue(method_exists($room, 'getId'));
    }
    
    public function testAsyncioV3Available(): void
    {
        // 验证 pfinal-asyncio v3.0 特性可用
        $this->assertTrue(function_exists('PfinalClub\\Asyncio\\run'));
        $this->assertTrue(function_exists('PfinalClub\\Asyncio\\create_task'));
        $this->assertTrue(function_exists('PfinalClub\\Asyncio\\sleep'));
        $this->assertTrue(function_exists('PfinalClub\\Asyncio\\gather'));
        $this->assertTrue(function_exists('PfinalClub\\Asyncio\\await'));
    }
    
    public function testNewFeaturesOptional(): void
    {
        // 新特性是可选的，不会影响现有代码
        $room = new class('new_features_test') extends \PfinalClub\AsyncioGamekit\Room\Room {
            protected function run(): mixed
            {
                return 'new_features_result';
            }
        };
        
        // 测试基本功能
        $this->assertEquals('new_features_test', $room->getId());
        $this->assertEquals('waiting', $room->getStatus());
        
        // 测试数据存储
        $room->set('test_data', 'test_value');
        $this->assertEquals('test_value', $room->get('test_data'));
        $this->assertTrue($room->has('test_data'));
    }
}