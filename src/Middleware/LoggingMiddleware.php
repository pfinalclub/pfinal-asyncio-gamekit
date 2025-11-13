<?php

namespace PfinalClub\AsyncioGamekit\Middleware;

use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\Logger\LoggerFactory;

/**
 * 日志中间件
 * 记录所有消息的处理过程
 */
class LoggingMiddleware implements MiddlewareInterface
{
    private bool $logData;

    public function __construct(bool $logData = false)
    {
        $this->logData = $logData;
    }

    public function handle(Player $player, string $event, mixed $data, callable $next): mixed
    {
        $startTime = microtime(true);

        $context = [
            'player_id' => $player->getId(),
            'player_name' => $player->getName(),
            'event' => $event,
        ];

        if ($this->logData) {
            $context['data'] = $data;
        }

        LoggerFactory::debug("Processing message", $context);

        try {
            $result = $next($player, $event, $data);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            LoggerFactory::debug("Message processed successfully", array_merge($context, [
                'duration_ms' => $duration,
            ]));

            return $result;
        } catch (\Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            LoggerFactory::error("Message processing failed", array_merge($context, [
                'duration_ms' => $duration,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]));

            throw $e;
        }
    }
}

