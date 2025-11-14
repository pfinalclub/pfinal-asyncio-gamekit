<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Room;

use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\Exceptions\RoomException;

/**
 * 房间接口
 * 
 * 定义房间的核心行为和契约
 */
interface RoomInterface
{
    /**
     * 获取房间ID
     */
    public function getId(): string;
    
    /**
     * 获取房间配置
     */
    public function getConfig(): array;
    
    /**
     * 获取房间状态
     */
    public function getStatus(): string;
    
    /**
     * 获取最大玩家数
     */
    public function getMaxPlayers(): int;
    
    /**
     * 添加玩家
     * 
     * @param Player $player
     * @return bool
     * @throws RoomException
     */
    public function addPlayer(Player $player): bool;
    
    /**
     * 移除玩家
     * 
     * @param string $playerId
     * @return bool
     */
    public function removePlayer(string $playerId): bool;
    
    /**
     * 获取玩家
     * 
     * @param string $playerId
     * @return Player|null
     */
    public function getPlayer(string $playerId): ?Player;
    
    /**
     * 获取所有玩家
     * 
     * @return array<string, Player>
     */
    public function getPlayers(): array;
    
    /**
     * 获取玩家数量
     */
    public function getPlayerCount(): int;
    
    /**
     * 广播消息给房间内所有玩家
     * 
     * @param string $event 事件名称
     * @param mixed $data 数据
     * @param Player|null $except 排除的玩家
     */
    public function broadcast(string $event, mixed $data = null, ?Player $except = null): void;
    
    /**
     * 异步广播（延迟执行）
     * 
     * @param string $event 事件名称
     * @param mixed $data 数据
     * @param float $delay 延迟时间（秒）
     * @return mixed
     */
    public function broadcastAsync(string $event, mixed $data = null, float $delay = 0): mixed;
    
    /**
     * 处理玩家消息（子类可重写）
     * 
     * @param Player $player 玩家
     * @param string $event 事件名称
     * @param mixed $data 数据
     * @return mixed
     */
    public function onPlayerMessage(Player $player, string $event, mixed $data): mixed;
    
    /**
     * 检查是否可以开始游戏
     */
    public function canStart(): bool;
    
    /**
     * 开始游戏
     * 
     * @return mixed
     */
    public function start(): mixed;
    
    /**
     * 销毁房间
     * 
     * @return mixed
     */
    public function destroy(): mixed;
    
    /**
     * 设置自定义数据
     * 
     * @param string $key 键
     * @param mixed $value 值
     */
    public function set(string $key, mixed $value): void;
    
    /**
     * 获取自定义数据
     * 
     * @param string $key 键
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;
    
    /**
     * 检查是否有指定数据
     * 
     * @param string $key 键
     * @return bool
     */
    public function has(string $key): bool;
    
    /**
     * 添加定时器
     * 
     * @param float $interval 间隔时间（秒）
     * @param callable $callback 回调函数
     * @param bool $persistent 是否持久化
     * @return int 定时器ID
     */
    public function addTimer(float $interval, callable $callback, bool $persistent = false): int;
    
    /**
     * 移除定时器
     * 
     * @param int $timerId 定时器ID
     * @return bool
     */
    public function removeTimer(int $timerId): bool;
    
    /**
     * 转换为数组（完整版）
     * 
     * @param bool $includePlayers 是否包含玩家详细信息
     * @return array
     */
    public function toArray(bool $includePlayers = true): array;
    
    /**
     * 转换为轻量级数组（不包含玩家详细信息）
     * 
     * @return array
     */
    public function toArrayLight(): array;
}
