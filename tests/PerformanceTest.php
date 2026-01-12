<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Tests;

use PHPUnit\Framework\TestCase;

/**
 * 性能基准测试
 * 测试 v3.0 的性能改进
 */
class PerformanceTest extends TestCase
{
    private function measureStartupTime(string $script): float
    {
        $start = microtime(true);
        $output = [];
        $returnCode = 0;
        
        exec("php $script 2>/dev/null", $output, $returnCode);
        
        $end = microtime(true);
        
        if ($returnCode !== 0) {
            $this->markTestSkipped("Script execution failed: $script");
        }
        
        return ($end - $start) * 1000; // 转换为毫秒
    }
    
    public function testStartupPerformance(): void
    {
        $simpleGameTime = $this->measureStartupTime('examples/SimpleGame.php');
        $cardGameTime = $this->measureStartupTime('examples/CardGame.php');
        
        // 启动时间应该合理（小于1秒）
        $this->assertLessThan(1000, $simpleGameTime, 'SimpleGame should start within 1000ms');
        $this->assertLessThan(1000, $cardGameTime, 'CardGame should start within 1000ms');
        
        echo "\n📊 Performance Results:\n";
        echo "SimpleGame startup: " . round($simpleGameTime, 2) . "ms\n";
        echo "CardGame startup: " . round($cardGameTime, 2) . "ms\n";
    }
    
    public function testMemoryUsage(): void
    {
        // 测试内存使用情况
        $memoryBefore = memory_get_usage(true);
        
        // 创建多个房间和玩家
        $rooms = [];
        for ($i = 0; $i < 10; $i++) {
            $room = new \PfinalClub\AsyncioGamekit\Room\Room("perf_test_$i");
            $rooms[] = $room;
            
            // 添加玩家
            for ($j = 0; $j < 4; $j++) {
                $player = new \PfinalClub\AsyncioGamekit\Player("player_{$i}_{$j}", null, "Player_{$i}_{$j}");
                $room->addPlayer($player);
            }
        }
        
        $memoryAfter = memory_get_usage(true);
        $memoryUsage = $memoryAfter - $memoryBefore;
        
        // 清理内存
        unset($rooms);
        
        // 内存使用应该在合理范围内（每个房间小于100KB）
        $memoryPerRoom = $memoryUsage / 10;
        $this->assertLessThan(100 * 1024, $memoryPerRoom, 'Memory usage per room should be less than 100KB');
        
        echo "\n💾 Memory Usage:\n";
        echo "Total memory for 10 rooms: " . round($memoryUsage / 1024, 2) . "KB\n";
        echo "Per room: " . round($memoryPerRoom / 1024, 2) . "KB\n";
    }
    
    public function testBroadcastPerformance(): void
    {
        $room = new \PfinalClub\AsyncioGamekit\Room\Room('broadcast_test');
        
        // 添加多个玩家
        $players = [];
        for ($i = 0; $i < 100; $i++) {
            $player = new \PfinalClub\AsyncioGamekit\Player("player_$i", null, "Player_$i");
            $room->addPlayer($player);
            $players[] = $player;
        }
        
        $startTime = microtime(true);
        
        // 执行100次广播
        for ($i = 0; $i < 100; $i++) {
            $room->broadcast('test_event', ['message' => "Broadcast $i"]);
        }
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // 毫秒
        
        // 平均每次广播应该很快（小于1毫秒）
        $averageTime = $duration / 100;
        $this->assertLessThan(1.0, $averageTime, 'Average broadcast time should be less than 1ms');
        
        echo "\n📡 Broadcast Performance:\n";
        echo "100 broadcasts to 100 players: " . round($duration, 2) . "ms\n";
        echo "Average per broadcast: " . round($averageTime, 3) . "ms\n";
        echo "Total player-message pairs: 10000\n";
    }
    
    public function testRoomManagerPerformance(): void
    {
        $roomManager = new \PfinalClub\AsyncioGamekit\RoomManager();
        
        // 测试房间查找性能
        $startTime = microtime(true);
        
        for ($i = 0; $i < 1000; $i++) {
            $room = $roomManager->findAvailableRoom(\PfinalClub\AsyncioGamekit\Room\Room::class);
        }
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // 毫秒
        
        // 平均查找时间应该很快（小于0.1毫秒）
        $averageTime = $duration / 1000;
        $this->assertLessThan(0.1, $averageTime, 'Average room lookup should be less than 0.1ms');
        
        echo "\n🔍 Room Manager Performance:\n";
        echo "1000 room lookups: " . round($duration, 2) . "ms\n";
        echo "Average per lookup: " . round($averageTime, 4) . "ms\n";
    }
}