<?php

namespace PfinalClub\AsyncioGamekit\Broadcast;

use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\Logger\LoggerFactory;
use function PfinalClub\Asyncio\create_task;

/**
 * 批量广播队列
 * 
 * 将消息暂存在队列中，定期批量发送以减少网络IO次数
 */
class BroadcastQueue
{
    /** @var array 消息队列 */
    private array $queue = [];

    /** @var float 上次刷新时间 */
    private float $lastFlush = 0;

    /** @var float 刷新间隔（秒） */
    private float $flushInterval = 0.05; // 50ms

    /** @var int 批处理大小 */
    private int $batchSize = 100;

    /** @var int 发送计数 */
    private int $sendCount = 0;

    /** @var int 批处理计数 */
    private int $batchCount = 0;

    public function __construct(float $flushInterval = 0.05, int $batchSize = 100)
    {
        $this->flushInterval = $flushInterval;
        $this->batchSize = $batchSize;
    }

    /**
     * 添加消息到队列
     */
    public function enqueue(Player $player, string $message): void
    {
        $playerId = $player->getId();
        
        if (!isset($this->queue[$playerId])) {
            $this->queue[$playerId] = [
                'player' => $player,
                'messages' => [],
            ];
        }

        $this->queue[$playerId]['messages'][] = $message;

        // 如果队列大小达到批处理大小，立即刷新
        if (count($this->queue) >= $this->batchSize) {
            $this->flush();
        }
    }

    /**
     * 刷新队列（发送所有待发送消息）
     */
    public function flush(): void
    {
        if (empty($this->queue)) {
            return;
        }

        $this->batchCount++;
        $this->lastFlush = microtime(true);

        foreach ($this->queue as $playerId => $data) {
            $player = $data['player'];
            $messages = $data['messages'];
            
            if (empty($messages)) {
                continue;
            }

            try {
                // 将多个消息合并成一个批量消息
                $batchMessage = json_encode([
                    'event' => 'batch',
                    'data' => ['messages' => $messages],
                    'timestamp' => microtime(true),
                ], JSON_THROW_ON_ERROR);

                $player->send($batchMessage, null, true);
                $this->sendCount += count($messages);
            } catch (\Throwable $e) {
                LoggerFactory::error("Failed to send batch messages", [
                    'player_id' => $playerId,
                    'message_count' => count($messages),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->queue = [];
    }

    /**
     * 自动刷新（如果超过刷新间隔）
     */
    public function autoFlush(): void
    {
        $now = microtime(true);
        
        if ($now - $this->lastFlush >= $this->flushInterval && !empty($this->queue)) {
            $this->flush();
        }
    }

    /**
     * 获取统计信息
     */
    public function getStats(): array
    {
        return [
            'queue_size' => count($this->queue),
            'send_count' => $this->sendCount,
            'batch_count' => $this->batchCount,
            'avg_batch_size' => $this->batchCount > 0 ? round($this->sendCount / $this->batchCount, 2) : 0,
            'flush_interval' => $this->flushInterval,
            'batch_size' => $this->batchSize,
        ];
    }

    /**
     * 重置统计信息
     */
    public function resetStats(): void
    {
        $this->sendCount = 0;
        $this->batchCount = 0;
    }

    /**
     * 设置刷新间隔
     */
    public function setFlushInterval(float $interval): void
    {
        $this->flushInterval = max(0.001, $interval);
    }

    /**
     * 设置批处理大小
     */
    public function setBatchSize(int $size): void
    {
        $this->batchSize = max(1, $size);
    }
}

