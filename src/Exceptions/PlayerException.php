<?php

namespace PfinalClub\AsyncioGamekit\Exceptions;

/**
 * 玩家相关异常
 */
class PlayerException extends GameException
{
    public const PLAYER_NOT_FOUND = 2001;
    public const PLAYER_DISCONNECTED = 2002;
    public const INVALID_PLAYER_ACTION = 2003;

    public static function playerNotFound(string $playerId): self
    {
        return new self(
            "Player {$playerId} not found",
            self::PLAYER_NOT_FOUND,
            null,
            ['player_id' => $playerId]
        );
    }

    public static function playerDisconnected(string $playerId): self
    {
        return new self(
            "Player {$playerId} is disconnected",
            self::PLAYER_DISCONNECTED,
            null,
            ['player_id' => $playerId]
        );
    }

    public static function invalidAction(string $playerId, string $action, string $reason = ''): self
    {
        return new self(
            "Invalid action '{$action}' from player {$playerId}" . ($reason ? ": {$reason}" : ''),
            self::INVALID_PLAYER_ACTION,
            null,
            ['player_id' => $playerId, 'action' => $action, 'reason' => $reason]
        );
    }
}

