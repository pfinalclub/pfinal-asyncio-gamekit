<?php

namespace PfinalClub\AsyncioGamekit\Tests;

use PHPUnit\Framework\TestCase;
use PfinalClub\AsyncioGamekit\Broadcast\BroadcastQueue;
use PfinalClub\AsyncioGamekit\Player;

class BroadcastQueueTest extends TestCase
{
    private BroadcastQueue $queue;
    private array $players;

    protected function setUp(): void
    {
        $this->queue = new BroadcastQueue();
        
        // 创建测试玩家
        $this->players = [
            new Player('player1', null, 'Player1'),
            new Player('player2', null, 'Player2'),
            new Player('player3', null, 'Player3'),
        ];
    }

    public function testEnqueueMessage(): void
    {
        $this->queue->enqueue($this->players[0], 'test_message');
        
        $stats = $this->queue->getStats();
        $this->assertEquals(1, $stats['queue_size']);
    }

    public function testEnqueueMultipleMessages(): void
    {
        $this->queue->enqueue($this->players[0], 'message1');
        $this->queue->enqueue($this->players[1], 'message2');
        $this->queue->enqueue($this->players[2], 'message3');
        
        $stats = $this->queue->getStats();
        $this->assertEquals(3, $stats['queue_size']);
    }

    public function testEnqueueMultipleMessagesForSamePlayer(): void
    {
        $this->queue->enqueue($this->players[0], 'message1');
        $this->queue->enqueue($this->players[0], 'message2');
        $this->queue->enqueue($this->players[0], 'message3');
        
        $stats = $this->queue->getStats();
        // 应该只有一个队列项（同一玩家的多条消息）
        $this->assertEquals(1, $stats['queue_size']);
    }

    public function testFlush(): void
    {
        $this->queue->enqueue($this->players[0], 'message1');
        $this->queue->enqueue($this->players[1], 'message2');
        
        $this->assertEquals(2, $this->queue->getStats()['queue_size']);
        
        $this->queue->flush();
        
        $this->assertEquals(0, $this->queue->getStats()['queue_size']);
    }

    public function testFlushEmptyQueue(): void
    {
        // 应该不抛出异常
        $this->queue->flush();
        
        $this->assertEquals(0, $this->queue->getStats()['queue_size']);
    }

    public function testGetStats(): void
    {
        $this->queue->enqueue($this->players[0], 'message1');
        $this->queue->enqueue($this->players[1], 'message2');
        
        $stats = $this->queue->getStats();
        
        $this->assertArrayHasKey('queue_size', $stats);
        $this->assertArrayHasKey('send_count', $stats);
        $this->assertArrayHasKey('batch_count', $stats);
        $this->assertArrayHasKey('avg_batch_size', $stats);
        $this->assertArrayHasKey('flush_interval', $stats);
        $this->assertArrayHasKey('batch_size', $stats);
        
        $this->assertEquals(2, $stats['queue_size']);
    }

    public function testSetFlushInterval(): void
    {
        $this->queue->setFlushInterval(0.1);
        
        $stats = $this->queue->getStats();
        
        $this->assertEquals(0.1, $stats['flush_interval']);
    }

    public function testSetBatchSize(): void
    {
        $this->queue->setBatchSize(50);
        
        $stats = $this->queue->getStats();
        
        $this->assertEquals(50, $stats['batch_size']);
    }

    public function testConstructorWithCustomParameters(): void
    {
        $queue = new BroadcastQueue(0.1, 50);
        
        $stats = $queue->getStats();
        
        $this->assertEquals(0.1, $stats['flush_interval']);
        $this->assertEquals(50, $stats['batch_size']);
    }

    public function testResetStats(): void
    {
        $this->queue->enqueue($this->players[0], 'message1');
        $this->queue->flush();
        
        $statsBefore = $this->queue->getStats();
        $this->assertGreaterThan(0, $statsBefore['send_count']);
        
        $this->queue->resetStats();
        
        $statsAfter = $this->queue->getStats();
        $this->assertEquals(0, $statsAfter['send_count']);
        $this->assertEquals(0, $statsAfter['batch_count']);
    }

    public function testAutoFlush(): void
    {
        $queue = new BroadcastQueue(0.001, 100); // 1ms 间隔
        $queue->enqueue($this->players[0], 'message1');
        
        $statsBefore = $queue->getStats();
        $this->assertGreaterThan(0, $statsBefore['queue_size']);
        
        // 等待足够长的时间确保刷新间隔已过
        usleep(10000); // 10ms >> 1ms
        
        $queue->autoFlush();
        
        $statsAfter = $queue->getStats();
        $this->assertEquals(0, $statsAfter['queue_size']);
        $this->assertGreaterThan(0, $statsAfter['batch_count']);
    }

    public function testBatchSizeAutoFlush(): void
    {
        $queue = new BroadcastQueue(1.0, 3); // 批处理大小为3（按玩家数量）
        
        // 添加2个不同玩家的消息，不应该自动刷新
        $queue->enqueue($this->players[0], "message1");
        $queue->enqueue($this->players[1], "message2");
        
        $stats = $queue->getStats();
        $this->assertEquals(2, $stats['queue_size']);
        
        // 添加第3个玩家的消息，应该触发自动刷新
        $queue->enqueue($this->players[2], "message3");
        
        $stats = $queue->getStats();
        $this->assertEquals(0, $stats['queue_size']);
        $this->assertGreaterThan(0, $stats['batch_count']);
    }

    public function testSetFlushIntervalMinimum(): void
    {
        $this->queue->setFlushInterval(-1); // 无效值
        
        $stats = $this->queue->getStats();
        
        // 应该设置为最小值 0.001
        $this->assertGreaterThanOrEqual(0.001, $stats['flush_interval']);
    }

    public function testSetBatchSizeMinimum(): void
    {
        $this->queue->setBatchSize(0); // 无效值
        
        $stats = $this->queue->getStats();
        
        // 应该设置为最小值 1
        $this->assertGreaterThanOrEqual(1, $stats['batch_size']);
    }

    public function testSendCount(): void
    {
        $this->queue->enqueue($this->players[0], 'message1');
        $this->queue->enqueue($this->players[0], 'message2');
        $this->queue->enqueue($this->players[0], 'message3');
        
        $this->queue->flush();
        
        $stats = $this->queue->getStats();
        
        // 应该发送了3条消息
        $this->assertEquals(3, $stats['send_count']);
        $this->assertEquals(1, $stats['batch_count']);
    }

    public function testAvgBatchSize(): void
    {
        // 第一批：3条消息
        $this->queue->enqueue($this->players[0], 'message1');
        $this->queue->enqueue($this->players[0], 'message2');
        $this->queue->enqueue($this->players[0], 'message3');
        $this->queue->flush();
        
        // 第二批：2条消息
        $this->queue->enqueue($this->players[1], 'message4');
        $this->queue->enqueue($this->players[1], 'message5');
        $this->queue->flush();
        
        $stats = $this->queue->getStats();
        
        // 平均批处理大小 = (3 + 2) / 2 = 2.5
        $this->assertEquals(2.5, $stats['avg_batch_size']);
    }
}
