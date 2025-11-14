<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Room;

use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\Exceptions\RoomException;

/**
 * 玩家管理接口
 */
interface PlayerManagerInterface
{
    /**
     * 添加玩家
     * @param Player $player
     * @return bool
     * @throws RoomException
     */
    public function addPlayer(Player $player): bool;
    
    /**
     * 移除玩家
     * @param string $playerId
     * @return bool
     */
    public function removePlayer(string $playerId): bool;
    
    /**
     * 获取玩家
     * @param string $playerId
     * @return Player|null
     */
    public function getPlayer(string $playerId): ?Player;
    
    /**
     * 获取所有玩家
     * @return array<string, Player>
     */
    public function getPlayers(): array;
    
    /**
     * 获取玩家数量
     * @return int
     */
    public function getPlayerCount(): int;
}