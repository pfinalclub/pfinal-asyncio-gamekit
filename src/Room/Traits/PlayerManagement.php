<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Room\Traits;

use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\Exceptions\RoomException;
use PfinalClub\AsyncioGamekit\Logger\LoggerFactory;
use PfinalClub\AsyncioGamekit\Constants\GameEvents;
use PfinalClub\AsyncioGamekit\Constants\RoomStatus;
use function PfinalClub\Asyncio\create_task;

/**
 * PlayerManagement Trait
 * 负责房间内玩家的管理功能
 */
trait PlayerManagement
{
    /** @var array<string, Player> 房间内的玩家 */
    protected array $players = [];

    /**
     * 添加玩家
     * 
     * @throws RoomException
     */
    public function addPlayer(Player $player): bool
    {
        if ($this->getPlayerCount() >= $this->cachedMaxPlayers) {
            throw RoomException::roomFull($this->id, $this->cachedMaxPlayers);
        }

        if (isset($this->players[$player->getId()])) {
            throw RoomException::playerAlreadyInRoom($player->getId(), $this->id);
        }

        if ($this->status === RoomStatus::RUNNING) {
            throw RoomException::roomAlreadyStarted($this->id);
        }

        $this->players[$player->getId()] = $player;
        $player->setRoom($this);
        
        // 更新缓存的玩家数量
        $this->cachedPlayerCount = count($this->players);

        // 【性能优化】清除玩家列表缓存（不影响其他缓存）
        $this->invalidatePlayersListCache();

        // 通知观察者
        $this->notifyPlayerJoined($player->getId());

        $this->onPlayerJoin($player);
        $this->broadcast(GameEvents::PLAYER_JOIN, $player->toArray());

        // 检查是否自动开始
        if (($this->config['auto_start'] ?? false) && $this->canStart()) {
            // 仅在异步环境中启动任务
            try {
                create_task(fn() => $this->start());
            } catch (\RuntimeException $e) {
                // 如果没有活动的CancellationScope，直接同步启动
                if (strpos($e->getMessage(), 'No active CancellationScope') !== false) {
                    $this->start();
                } else {
                    throw $e;
                }
            }
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
        
        // 更新缓存的玩家数量
        $this->cachedPlayerCount = count($this->players);

        // 【性能优化】清除玩家列表缓存（不影响其他缓存）
        $this->invalidatePlayersListCache();

        // 通知观察者
        $this->notifyPlayerLeft($playerId);

        $this->onPlayerLeave($player);
        $this->broadcast(GameEvents::PLAYER_LEAVE, ['player_id' => $playerId]);

        // 如果房间为空且未运行，延迟销毁（给新玩家加入的机会）
        if (empty($this->players) && $this->status !== RoomStatus::RUNNING) {
            // 仅在异步环境中启动延迟销毁任务
            try {
                create_task(function() {
                    \PfinalClub\Asyncio\sleep(5); // 延迟 5 秒
                    // 再次检查是否仍为空
                    if (empty($this->players)) {
                        LoggerFactory::info("Auto-destroying empty room {room_id}", [
                            'room_id' => $this->id
                        ]);
                        $this->destroy();
                    }
                });
            } catch (\RuntimeException $e) {
                // 如果没有活动的CancellationScope，跳过延迟销毁
                if (strpos($e->getMessage(), 'No active CancellationScope') === false) {
                    throw $e;
                }
            }
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

    /** @var int|null 缓存的玩家数量 */
    private ?int $cachedPlayerCount = null;

    /**
     * 获取玩家数量（带缓存）
     */
    public function getPlayerCount(): int
    {
        if ($this->cachedPlayerCount === null) {
            $this->cachedPlayerCount = count($this->players);
        }
        return $this->cachedPlayerCount;
    }

    /**
     * 检查玩家是否在房间中
     */
    public function hasPlayer($player): bool
    {
        if ($player instanceof Player) {
            return isset($this->players[$player->getId()]);
        }
        
        if (is_string($player)) {
            return isset($this->players[$player]);
        }
        
        return false;
    }

    /**
     * 玩家加入时的回调
     */
    protected function onPlayerJoin(Player $player): void
    {
        LoggerFactory::info("Player {player_name} joined room {room_id}", [
            'player_name' => $player->getName(),
            'player_id' => $player->getId(),
            'room_id' => $this->id,
        ]);
    }

    /**
     * 玩家离开时调用（子类可重写）
     */
    protected function onPlayerLeave(Player $player): void
    {
        LoggerFactory::info("Player {player_name} left room {room_id}", [
            'player_name' => $player->getName(),
            'player_id' => $player->getId(),
            'room_id' => $this->id,
        ]);
    }
}

