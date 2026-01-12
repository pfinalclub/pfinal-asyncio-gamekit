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
            $this->tryStartRoom();
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
            $this->scheduleEmptyRoomDestroy();
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

    /**
     * 尝试启动房间（处理异步/同步环境）
     */
    private function tryStartRoom(): void
    {
        try {
            create_task(fn() => $this->start());
        } catch (\RuntimeException $e) {
            // 检查是否是 CancellationScope 相关异常
            if (str_contains($e->getMessage(), 'No active CancellationScope')) {
                // 同步环境，直接启动（带异常处理）
                try {
                    $this->start();
                } catch (\Throwable $startError) {
                    LoggerFactory::error("Failed to start room synchronously", [
                        'room_id' => $this->id,
                        'error' => $startError->getMessage(),
                        'exception' => get_class($startError),
                        'trace' => $startError->getTraceAsString(),
                    ]);
                    // 重新抛出异常，让上层处理
                    throw $startError;
                }
            } else {
                // 其他运行时异常，记录并重新抛出
                LoggerFactory::error("Failed to create async task for room start", [
                    'room_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        } catch (\Throwable $e) {
            // 捕获所有异常，记录但不中断流程
            LoggerFactory::error("Unexpected error while starting room", [
                'room_id' => $this->id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * 调度空房间延迟销毁
     */
    private function scheduleEmptyRoomDestroy(): void
    {
        // 获取配置的延迟时间，默认 5 秒
        $delay = $this->config['empty_room_destroy_delay'] ?? 5;
        
        try {
            create_task(function() use ($delay) {
                \PfinalClub\Asyncio\sleep($delay);
                // 再次检查是否仍为空
                if (empty($this->players)) {
                    LoggerFactory::info("Auto-destroying empty room {room_id}", [
                        'room_id' => $this->id
                    ]);
                    $this->destroy();
                }
            });
        } catch (\RuntimeException $e) {
            // 非异步环境，跳过延迟销毁
            if (!str_contains($e->getMessage(), 'No active CancellationScope')) {
                throw $e;
            }
            // 同步环境下记录日志，不执行延迟销毁
            LoggerFactory::debug("Skipping delayed destroy in sync environment", [
                'room_id' => $this->id
            ]);
        }
    }
}

