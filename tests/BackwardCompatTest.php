<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Tests;

use PHPUnit\Framework\TestCase;
use PfinalClub\AsyncioGamekit\{Room, Player, RoomManager, GameServer};

/**
 * 向后兼容性测试
 * 确保 v2.x 代码在 v3.0 下仍能正常运行
 */
class BackwardCompatTest extends TestCase
{
    public function testRoomBasicCompatibility(): void
    {
        // 测试基础的 Room 类仍然可用
        $room = new class('test_room') extends Room {
            protected function run(): mixed
            {
                return 'test_result';
            }
        };
        
        $this->assertEquals('test_room', $room->getId());
        $this->assertEquals('waiting', $room->getStatus());
    }
    
    public function testPlayerBasicCompatibility(): void
    {
        // 测试基础的 Player 类仍然可用
        $player = new Player('test_player', null, 'TestPlayer');
        
        $this->assertEquals('test_player', $player->getId());
        $this->assertEquals('TestPlayer', $player->getName());
        $this->assertFalse($player->isReady());
        
        // 测试数据存储
        $player->set('score', 100);
        $this->assertEquals(100, $player->get('score'));
        $this->assertTrue($player->has('score'));
    }
    
    public function testRoomManagerBasicCompatibility(): void
    {
        // 测试基础的 RoomManager 类仍然可用
        $roomManager = new RoomManager();
        
        // 创建房间
        $room = $roomManager->createRoom(Room::class, 'compat_test_room');
        $this->assertInstanceOf(Room::class, $room);
        $this->assertEquals('compat_test_room', $room->getId());
        
        // 获取统计
        $stats = $roomManager->getStats();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_rooms', $stats);
        $this->assertArrayHasKey('total_players', $stats);
    }
    
    public function testGameServerBasicCompatibility(): void
    {
        // 测试基础的 GameServer 类仍然可用
        $server = new GameServer('127.0.0.1', 2346, [
            'name' => 'TestServer',
            'count' => 1,
        ]);
        
        $this->assertInstanceOf(GameServer::class, $server);
        $this->assertInstanceOf(\PfinalClub\AsyncioGamekit\RoomManager::class, $server->getRoomManager());
    }
    
    public function testRoomLifecycleCompatibility(): void
    {
        // 测试 Room 生命周期方法兼容性
        $room = new class('lifecycle_test') extends Room {
            protected function run(): mixed
            {
                $this->set('created', true);
                $this->broadcast('test_event', ['test' => true]);
                return 'lifecycle_result';
            }
            
            protected function onCreate(): mixed
            {
                $this->set('created', true);
                return null;
            }
            
            protected function onStart(): mixed
            {
                $this->set('started', true);
                return null;
            }
            
            protected function onDestroy(): mixed
            {
                $this->set('destroyed', true);
                return null;
            }
        };
        
        // 添加玩家
        $player = new Player('test_player', null, 'TestPlayer');
        $room->addPlayer($player);
        
        $this->assertTrue($room->get('created'));
        $this->assertFalse($room->canStart());
        
        $player->setReady(true);
        $this->assertTrue($room->canStart());
        
        // 测试生命周期钩子（基类默认实现返回 null）
        $this->assertNull($room->onCreate());
        $this->assertNull($room->onStart());
        $this->assertEquals('lifecycle_result', $room->start());
        $this->assertTrue($room->get('destroyed'));
    }
    
    public function testBroadcastCompatibility(): void
    {
        // 测试广播功能兼容性
        $room = new class('broadcast_test') extends Room {
            protected function run(): mixed
            {
                return 'broadcast_result';
            }
        };
        
        $player1 = new Player('player1', null, 'Player1');
        $player2 = new Player('player2', null, 'Player2');
        
        $room->addPlayer($player1);
        $room->addPlayer($player2);
        
        // 测试广播给所有玩家
        $messageSent = $room->broadcast('test_event', ['message' => 'Hello World']);
        $this->assertNull($messageSent); // 广播方法没有返回值
        
        // 测试排除特定玩家的广播
        $messageSent2 = $room->broadcast('test_event', ['message' => 'Hello'], $player1);
        $this->assertNull($messageSent2);
    }
    
    public function testOldNamespaceStillWorks(): void
    {
        // 测试旧的命名空间仍然可用（兼容层）
        $room = new class('test_room') extends \PfinalClub\AsyncioGamekit\Room {
            protected function run(): mixed
            {
                return 'old_namespace_works';
            }
        };
        
        $this->assertEquals('test_room', $room->getId());
        $this->assertEquals('old_namespace_works', $room->start());
    }
}