<?php

namespace PfinalClub\AsyncioGamekit\Exceptions;

/**
 * 房间相关异常
 */
class RoomException extends GameException
{
    public const ROOM_FULL = 1001;
    public const ROOM_NOT_FOUND = 1002;
    public const ROOM_ALREADY_STARTED = 1003;
    public const ROOM_NOT_READY = 1004;
    public const PLAYER_ALREADY_IN_ROOM = 1005;
    public const PLAYER_NOT_IN_ROOM = 1006;

    public static function roomFull(string $roomId, int $maxPlayers): self
    {
        return new self(
            "Room {$roomId} is full (max: {$maxPlayers})",
            self::ROOM_FULL,
            null,
            ['room_id' => $roomId, 'max_players' => $maxPlayers]
        );
    }

    public static function roomNotFound(string $roomId): self
    {
        return new self(
            "Room {$roomId} not found",
            self::ROOM_NOT_FOUND,
            null,
            ['room_id' => $roomId]
        );
    }

    public static function roomAlreadyStarted(string $roomId): self
    {
        return new self(
            "Room {$roomId} has already started",
            self::ROOM_ALREADY_STARTED,
            null,
            ['room_id' => $roomId]
        );
    }

    public static function roomNotReady(string $roomId, int $currentPlayers, int $minPlayers): self
    {
        return new self(
            "Room {$roomId} is not ready to start (current: {$currentPlayers}, min: {$minPlayers})",
            self::ROOM_NOT_READY,
            null,
            ['room_id' => $roomId, 'current_players' => $currentPlayers, 'min_players' => $minPlayers]
        );
    }

    public static function playerAlreadyInRoom(string $playerId, string $roomId): self
    {
        return new self(
            "Player {$playerId} is already in room {$roomId}",
            self::PLAYER_ALREADY_IN_ROOM,
            null,
            ['player_id' => $playerId, 'room_id' => $roomId]
        );
    }

    public static function playerNotInRoom(string $playerId): self
    {
        return new self(
            "Player {$playerId} is not in any room",
            self::PLAYER_NOT_IN_ROOM,
            null,
            ['player_id' => $playerId]
        );
    }
}

