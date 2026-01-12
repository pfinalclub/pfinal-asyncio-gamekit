<?php

namespace PfinalClub\AsyncioGamekit\Tests;

use PHPUnit\Framework\TestCase;
use PfinalClub\AsyncioGamekit\Audit\AuditLogger;
use PfinalClub\AsyncioGamekit\Player;

class AuditLoggerTest extends TestCase
{
    private AuditLogger $logger;

    protected function setUp(): void
    {
        // 禁用日志输出，避免测试时产生大量日志
        $this->logger = new AuditLogger(false);
    }

    public function testLog(): void
    {
        $this->logger->log('test_action', ['key' => 'value', 'user_id' => 'user1']);
        
        $logs = $this->logger->getLogs();
        
        $this->assertCount(1, $logs);
        $this->assertEquals('test_action', $logs[0]['action']);
        $this->assertEquals('user1', $logs[0]['user_id']);
        $this->assertArrayHasKey('timestamp', $logs[0]);
    }

    public function testLogPlayerJoin(): void
    {
        $player = new Player('player1', null, 'TestPlayer');
        
        $this->logger->log('player.join', [
            'user_id' => $player->getId(),
            'player_name' => $player->getName(),
            'room_id' => 'room_001',
        ]);
        
        $logs = $this->logger->getLogs();
        
        $this->assertCount(1, $logs);
        $this->assertEquals('player.join', $logs[0]['action']);
        $this->assertEquals('player1', $logs[0]['user_id']);
    }

    public function testLogPlayerLeave(): void
    {
        $player = new Player('player1', null, 'TestPlayer');
        
        $this->logger->log('player.leave', [
            'user_id' => $player->getId(),
            'room_id' => 'room_001',
            'reason' => 'voluntary',
        ]);
        
        $logs = $this->logger->getLogs();
        
        $this->assertCount(1, $logs);
        $this->assertEquals('player.leave', $logs[0]['action']);
    }

    public function testLogRoomCreate(): void
    {
        $this->logger->log('room.create', [
            'room_id' => 'room_001',
            'room_class' => 'TestRoom',
            'creator_id' => 'creator1',
        ]);
        
        $logs = $this->logger->getLogs();
        
        $this->assertCount(1, $logs);
        $this->assertEquals('room.create', $logs[0]['action']);
    }

    public function testLogRoomDestroy(): void
    {
        $this->logger->log('room.destroy', [
            'room_id' => 'room_001',
            'room_class' => 'TestRoom',
            'reason' => 'completed',
        ]);
        
        $logs = $this->logger->getLogs();
        
        $this->assertCount(1, $logs);
        $this->assertEquals('room.destroy', $logs[0]['action']);
    }

    public function testLogPermissionDenied(): void
    {
        $player = new Player('player1', null, 'TestPlayer');
        
        $this->logger->log('permission.denied', [
            'user_id' => $player->getId(),
            'permission' => 'room.kick',
            'room_id' => 'room_001',
        ]);
        
        $logs = $this->logger->getLogs();
        
        $this->assertCount(1, $logs);
        $this->assertEquals('permission.denied', $logs[0]['action']);
    }

    public function testLogSecurityEvent(): void
    {
        $this->logger->log('security.rate_limit_exceeded', [
            'user_id' => 'user1',
            'ip' => '127.0.0.1',
            'requests' => 100,
        ]);
        
        $logs = $this->logger->getLogs();
        
        $this->assertCount(1, $logs);
        $this->assertEquals('security.rate_limit_exceeded', $logs[0]['action']);
    }

    public function testLogMultipleEntries(): void
    {
        $this->logger->log('action1', ['user_id' => 'user1', 'data' => 'test1']);
        $this->logger->log('action2', ['user_id' => 'user2', 'data' => 'test2']);
        $this->logger->log('action3', ['user_id' => 'user3', 'data' => 'test3']);
        
        $logs = $this->logger->getLogs();
        
        $this->assertCount(3, $logs);
    }

    public function testGetLogsByAction(): void
    {
        $this->logger->log('action1', ['user_id' => 'user1']);
        $this->logger->log('action2', ['user_id' => 'user2']);
        $this->logger->log('action1', ['user_id' => 'user3']);
        
        $logs = $this->logger->getLogsByAction('action1');
        
        $this->assertCount(2, $logs);
    }

    public function testGetLogsByUser(): void
    {
        $this->logger->log('action1', ['user_id' => 'user1']);
        $this->logger->log('action2', ['user_id' => 'user2']);
        $this->logger->log('action3', ['user_id' => 'user1']);
        
        $logs = $this->logger->getLogsByUser('user1');
        
        $this->assertCount(2, $logs);
    }

    public function testClear(): void
    {
        $this->logger->log('action1', ['user_id' => 'user1']);
        $this->logger->log('action2', ['user_id' => 'user2']);
        
        $this->assertCount(2, $this->logger->getLogs());
        
        $this->logger->clear();
        
        $this->assertCount(0, $this->logger->getLogs());
    }

    public function testGetStats(): void
    {
        $this->logger->log('action1', ['user_id' => 'user1']);
        $this->logger->log('action1', ['user_id' => 'user2']);
        $this->logger->log('action2', ['user_id' => 'user3']);
        
        $stats = $this->logger->getStats();
        
        $this->assertArrayHasKey('total_logs', $stats);
        $this->assertArrayHasKey('max_logs', $stats);
        $this->assertArrayHasKey('action_counts', $stats);
        
        $this->assertEquals(3, $stats['total_logs']);
        $this->assertEquals(2, $stats['action_counts']['action1']);
        $this->assertEquals(1, $stats['action_counts']['action2']);
    }

    public function testGetLogsWithLimitAndOffset(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->logger->log("action$i", ['user_id' => "user$i"]);
        }
        
        $logs = $this->logger->getLogs(5, 0);
        $this->assertCount(5, $logs);
        
        $logs = $this->logger->getLogs(5, 5);
        $this->assertCount(5, $logs);
    }

    public function testLogWithoutUserId(): void
    {
        $this->logger->log('system_action', []);
        
        $logs = $this->logger->getLogs();
        
        $this->assertCount(1, $logs);
        $this->assertEquals('system', $logs[0]['user_id']);
    }

    public function testLogFormat(): void
    {
        $this->logger->log('test_action', ['user_id' => 'test_user', 'test' => 'data']);
        
        $logs = $this->logger->getLogs();
        $log = $logs[0];
        
        $this->assertArrayHasKey('timestamp', $log);
        $this->assertArrayHasKey('datetime', $log);
        $this->assertArrayHasKey('action', $log);
        $this->assertArrayHasKey('user_id', $log);
        $this->assertArrayHasKey('ip', $log);
        $this->assertArrayHasKey('context', $log);
        
        $this->assertEquals('test_action', $log['action']);
        $this->assertEquals('test_user', $log['user_id']);
    }

    public function testMaxLogsLimit(): void
    {
        // 测试最大日志数量限制
        // 使用较小的数量进行测试，避免产生过多日志
        for ($i = 0; $i < 10005; $i++) {
            $this->logger->log("action$i", ['user_id' => "user$i"]);
        }
        
        $stats = $this->logger->getStats();
        
        // 应该限制在 10000 条
        $this->assertLessThanOrEqual(10000, $stats['total_logs']);
        $this->assertEquals(10000, $stats['total_logs']);
    }
}
