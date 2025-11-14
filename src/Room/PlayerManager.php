<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Room;

use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\Exceptions\RoomException;
use PfinalClub\AsyncioGamekit\Logger\LoggerFactory;
use function PfinalClub\Asyncio\create_task;

/**
 * 玩家管理实现类
 */
class PlayerManager implements PlayerManagerInterface
{
    /** @var OptimizedRoom 所属房间 */
    protected OptimizedRoom $room;
    
    /** @var array<string, Player> 房间内的玩家 */
    protected array $players = [];

    /**
     * @param OptimizedRoom $room 所属房间
     */
    public function __construct(OptimizedRoom $room)
    {
        $this->room = $room;
    }

    /**
     * 添加玩家
     * 
     * @throws RoomException
     */
    public function addPlayer(Player $player): bool
    {
        $maxPlayers = $this->room->getConfig()->get('max_players', 4);
        
        if (count($this->players) >= $maxPlayers) {
            throw RoomException::roomFull($this->room->getId(), $maxPlayers);
        }

        if (isset($this->players[$player->getId()])) {
            throw RoomException::playerAlreadyInRoom($player->getId(), $this->room->getId());
        }

        if ($this->room->getStatus() === 'running') {
            throw RoomException::roomAlreadyStarted($this->room->getId());
        }

        $this->players[$player->getId()] = $player;
        $player->setRoom($this->room);

        // 调用房间的钩子方法
        $this->room->onPlayerJoin($player);
        
        // 广播玩家加入消息
        $this->room->broadcast('player:join', $player->toArray());

        // 检查是否自动开始
        if ($this->room->getConfig()->get('auto_start', false) && $this->room->canStart()) {
            create_task(fn() => $this->room->start());
        }

        return true;
    }

    /**
     * 移除玩家
     */
    public function removePlayer(string $playerId): bool
    {
        if (!isset($this->players[$playerId])) {
            return false;
        }

        $player = $this->players[$playerId];
        unset($this->players[$playerId]);
        $player->setRoom(null);

        // 调用房间的钩子方法
        $this->room->onPlayerLeave($player);
        
        // 广播玩家离开消息
        $this->room->broadcast('player:leave', ['player_id' => $playerId]);

        // 如果房间为空且未运行，延迟销毁（给新玩家加入的机会）
        if (empty($this->players) && $this->room->getStatus() !== 'running') {
            create_task(function() {
                \PfinalClub\Asyncio\sleep(5); // 延迟 5 秒
                // 再次检查是否仍为空
                if (empty($this->players)) {
                    LoggerFactory::info("Auto-destroying empty room {room_id}", [
                        'room_id' => $this->room->getId()
                    ]);
                    $this->room->destroy();
                }
            });
        }

        return true;
    }

    /**
     * 获取玩家
     */
    public function getPlayer(string $playerId): ?Player
    {
        return $this->players[$playerId] ?? null;
    }

    /**
     * 获取所有玩家
     * 
     * @return array<string, Player>
     */
    public function getPlayers(): array
    {
        return $this->players;
    }

    /**
     * 获取玩家数量
     */
    public function getPlayerCount(): int
    {
        return count($this->players);
    }
}