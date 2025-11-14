<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Exceptions;

/**
 * 服务器相关异常
 */
class ServerException extends GameException
{
    public const SERVER_ERROR = 3001;
    public const INVALID_MESSAGE_FORMAT = 3002;
    public const RATE_LIMIT_EXCEEDED = 3003;

    public static function serverError(string $message, ?\Throwable $previous = null): self
    {
        return new self(
            "Server error: {$message}",
            self::SERVER_ERROR,
            $previous
        );
    }

    public static function invalidMessageFormat(string $message): self
    {
        return new self(
            "Invalid message format: {$message}",
            self::INVALID_MESSAGE_FORMAT,
            null,
            ['raw_message' => $message]
        );
    }

    public static function rateLimitExceeded(string $playerId, int $limit): self
    {
        return new self(
            "Rate limit exceeded for player {$playerId} (limit: {$limit} requests)",
            self::RATE_LIMIT_EXCEEDED,
            null,
            ['player_id' => $playerId, 'limit' => $limit]
        );
    }
}

