<?php

namespace PfinalClub\AsyncioGamekit;

use Generator;
use function PfinalClub\Asyncio\{create_task, sleep, get_event_loop};

/**
 * Room 房间基类
 * 提供异步游戏逻辑编排能力
 */
abstract class Room
{
    /** @var string 房间ID */
    protected string $id;
    
    /** @var array<string, Player> 房间内的玩家 */
    protected array $players = [];
    
    /** @var string 房间状态 */
    protected string $status = 'waiting'; // waiting, running, finished
    
    /** @var array 房间自定义数据 */
    protected array $data = [];
    
    /** @var array 定时任务ID列表 */
    protected array $timerIds = [];
    
    /** @var bool 是否正在运行 */
    protected bool $isRunning = false;
    
    /** @var array 房间配置 */
    protected array $config = [];

    /**
     * @param string $id 房间ID
     * @param array $config 房间配置
     */
    public function __construct(string $id, array $config = [])
    {
        $this->id = $id;
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * 获取默认配置
     */
    protected function getDefaultConfig(): array
    {
        return [
            'max_players' => 4,
            'min_players' => 2,
            'auto_start' => false,
        ];
    }

    /**
     * 获取房间ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * 获取房间状态
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * 设置房间状态
     */
    protected function setStatus(string $status): void
    {
        $this->status = $status;
        $this->broadcast('room:status_changed', [
            'status' => $status,
            'room_id' => $this->id
        ]);
    }

    /**
     * 添加玩家
     */
    public function addPlayer(Player $player): bool
    {
        if (count($this->players) >= $this->config['max_players']) {
            return false;
        }

        if (isset($this->players[$player->getId()])) {
            return false;
        }

        $this->players[$player->getId()] = $player;
        $player->setRoom($this);

        $this->onPlayerJoin($player);
        $this->broadcast('player:join', $player->toArray());

        // 检查是否自动开始
        if (($this->config['auto_start'] ?? false) && $this->canStart()) {
            create_task($this->start());
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

        $this->onPlayerLeave($player);
        $this->broadcast('player:leave', ['player_id' => $playerId]);

        // 如果房间为空，销毁房间
        if (empty($this->players)) {
            create_task($this->destroy());
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

    /**
     * 广播消息给房间内所有玩家
     */
    public function broadcast(string $event, mixed $data = null, ?Player $except = null): void
    {
        foreach ($this->players as $player) {
            if ($except && $player->getId() === $except->getId()) {
                continue;
            }
            $player->send($event, $data);
        }
    }

    /**
     * 异步广播（延迟执行）
     */
    public function broadcastAsync(string $event, mixed $data = null, float $delay = 0): Generator
    {
        if ($delay > 0) {
            yield sleep($delay);
        }
        $this->broadcast($event, $data);
    }

    /**
     * 检查是否可以开始游戏
     */
    public function canStart(): bool
    {
        $playerCount = count($this->players);
        return $playerCount >= $this->config['min_players'] 
            && $playerCount <= $this->config['max_players']
            && !$this->isRunning;
    }

    /**
     * 启动房间游戏逻辑
     */
    public function start(): Generator
    {
        if (!$this->canStart()) {
            return;
        }

        $this->isRunning = true;
        $this->setStatus('running');

        yield from $this->onCreate();
        yield from $this->onStart();
        yield from $this->run();
    }

    /**
     * 添加定时任务
     * 
     * @param float $interval 时间间隔（秒）
     * @param callable $callback 回调函数
     * @param bool $repeat 是否重复执行
     * @return int 定时器ID
     */
    protected function addTimer(float $interval, callable $callback, bool $repeat = false): int
    {
        $loop = get_event_loop();
        $timerId = $loop->addTimer($interval, $callback, $repeat);
        $this->timerIds[] = $timerId;
        return $timerId;
    }

    /**
     * 删除定时任务
     */
    protected function removeTimer(int $timerId): void
    {
        $loop = get_event_loop();
        $loop->delTimer($timerId);
        $this->timerIds = array_filter($this->timerIds, fn($id) => $id !== $timerId);
    }

    /**
     * 延迟执行任务
     */
    protected function delay(float $seconds): Generator
    {
        yield sleep($seconds);
    }

    /**
     * 销毁房间
     */
    public function destroy(): Generator
    {
        if ($this->isRunning) {
            $this->isRunning = false;
        }

        yield from $this->onDestroy();

        // 清除所有定时器
        foreach ($this->timerIds as $timerId) {
            $this->removeTimer($timerId);
        }
        $this->timerIds = [];

        // 移除所有玩家
        foreach ($this->players as $player) {
            $player->setRoom(null);
        }
        $this->players = [];

        $this->setStatus('finished');
    }

    /**
     * 设置自定义数据
     */
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * 获取自定义数据
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'player_count' => count($this->players),
            'players' => array_map(fn($p) => $p->toArray(), $this->players),
            'config' => $this->config,
            'data' => $this->data
        ];
    }

    // ==================== 生命周期钩子 ====================

    /**
     * 房间创建时调用
     */
    protected function onCreate(): Generator
    {
        // 子类可重写
        yield;
    }

    /**
     * 游戏开始时调用
     */
    protected function onStart(): Generator
    {
        // 子类可重写
        yield;
    }

    /**
     * 游戏主循环（必须实现）
     */
    abstract protected function run(): Generator;

    /**
     * 房间销毁时调用
     */
    protected function onDestroy(): Generator
    {
        // 子类可重写
        yield;
    }

    /**
     * 玩家加入时调用
     */
    protected function onPlayerJoin(Player $player): void
    {
        // 子类可重写
    }

    /**
     * 玩家离开时调用
     */
    protected function onPlayerLeave(Player $player): void
    {
        // 子类可重写
    }

    /**
     * 处理玩家消息
     */
    public function onPlayerMessage(Player $player, string $event, mixed $data): Generator
    {
        // 子类可重写
        yield;
    }
}

