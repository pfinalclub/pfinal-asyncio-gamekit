# pfinal-asyncio-gamekit 开发指南

## 目录

- [核心概念](#核心概念)
- [设计模式](#设计模式)
- [最佳实践](#最佳实践)
- [常见问题](#常见问题)
- [性能优化](#性能优化)

## 核心概念

### 1. 协程与异步

pfinal-asyncio-gamekit 基于 PHP Fiber 实现协程，让你可以用同步的方式编写异步代码。

```php
function myAsyncFunction(): mixed
{
    // 异步等待
    sleep(1);
    
    // 并发执行
    $results = gather(
        create_task(fn() => $this->task1()),
        create_task(fn() => $this->task2())
    );
    
    return $results;
}
```

**关键点：**
- 所有异步函数必须返回 `mixed`
- 使用 `sleep()` 来等待异步操作
- 使用 `await()` 来调用其他异步函数

### 2. 房间生命周期

```
创建房间 (constructor)
    ↓
onCreate() - 初始化房间数据
    ↓
等待玩家加入 (waiting)
    ↓
onStart() - 游戏开始前的准备
    ↓
run() - 游戏主逻辑 (running)
    ↓
onDestroy() - 清理资源 (finished)
```

### 3. 消息流

```
客户端 → WebSocket → GameServer → Room → Player
                         ↑              ↓
                         ←── broadcast ←
```

## 设计模式

### 1. 状态机模式

适用于有明确状态转换的游戏。

```php
class StateMachineRoom extends Room
{
    private string $gameState = 'init';
    
    protected function run(): Generator
    {
        while ($this->isRunning) {
            match ($this->gameState) {
                'init' => yield from $this->stateInit(),
                'playing' => yield from $this->statePlaying(),
                'ending' => yield from $this->stateEnding(),
                default => break 2,
            };
        }
    }
    
    private function stateInit(): Generator
    {
        $this->broadcast('state:init', []);
        yield sleep(3);
        $this->gameState = 'playing';
    }
    
    private function statePlaying(): Generator
    {
        // 游戏逻辑
        yield sleep(10);
        $this->gameState = 'ending';
    }
    
    private function stateEnding(): Generator
    {
        $this->broadcast('state:ending', []);
        yield sleep(2);
        yield from $this->destroy();
    }
}
```

### 2. 回合制游戏模式

```php
class TurnBasedRoom extends Room
{
    private int $currentTurn = 0;
    private array $playerOrder = [];
    
    protected function run(): Generator
    {
        $this->playerOrder = array_values($this->players);
        $totalRounds = 5;
        
        for ($round = 1; $round <= $totalRounds; $round++) {
            foreach ($this->playerOrder as $player) {
                yield from $this->playerTurn($player);
            }
        }
        
        yield from $this->destroy();
    }
    
    private function playerTurn(Player $player): Generator
    {
        $this->currentTurn++;
        
        $this->broadcast('turn:start', [
            'player_id' => $player->getId(),
            'turn' => $this->currentTurn
        ]);
        
        // 设置超时
        $player->set('turn_done', false);
        $timeout = 30;
        $start = time();
        
        while (!$player->get('turn_done') && (time() - $start) < $timeout) {
            yield sleep(0.5);
        }
        
        if (!$player->get('turn_done')) {
            // 超时处理
            $this->handleTimeout($player);
        }
        
        $this->broadcast('turn:end', [
            'player_id' => $player->getId()
        ]);
    }
}
```

### 3. 实时对战模式

```php
class RealtimeBattleRoom extends Room
{
    private array $battleQueue = [];
    
    protected function run(): Generator
    {
        // 启动战斗处理循环
        $this->addTimer(0.1, function() {
            $this->processBattleQueue();
        }, true);
        
        // 主循环
        $duration = 180; // 3分钟对战
        $start = time();
        
        while (time() - $start < $duration && $this->isRunning) {
            yield sleep(1);
            
            // 更新游戏状态
            $this->updateGameState();
            $this->broadcast('game:update', $this->getGameState());
        }
        
        yield from $this->endBattle();
    }
    
    private function processBattleQueue(): void
    {
        while (!empty($this->battleQueue)) {
            $action = array_shift($this->battleQueue);
            $this->executeAction($action);
        }
    }
    
    public function onPlayerMessage(Player $player, string $event, mixed $data): Generator
    {
        if ($event === 'action') {
            // 将玩家动作加入队列
            $this->battleQueue[] = [
                'player' => $player,
                'action' => $data,
                'timestamp' => microtime(true)
            ];
        }
        yield;
    }
}
```

## 最佳实践

### 1. 错误处理

```php
protected function run(): Generator
{
    try {
        yield from $this->gameLogic();
    } catch (\Exception $e) {
        // 记录错误
        error_log("Room {$this->id} error: {$e->getMessage()}");
        
        // 通知玩家
        $this->broadcast('error', [
            'message' => '游戏出现错误，即将退出'
        ]);
        
        // 清理并退出
        yield sleep(2);
        yield from $this->destroy();
    }
}
```

### 2. 资源清理

```php
protected function onDestroy(): Generator
{
    // 1. 清除所有定时器
    foreach ($this->timerIds as $timerId) {
        $this->removeTimer($timerId);
    }
    
    // 2. 保存游戏数据
    yield from $this->saveGameData();
    
    // 3. 通知所有玩家
    $this->broadcast('room:destroyed', [
        'reason' => 'Game ended'
    ]);
    
    // 4. 清理资源
    $this->data = [];
    $this->players = [];
    
    yield;
}
```

### 3. 数据验证

```php
public function onPlayerMessage(Player $player, string $event, mixed $data): Generator
{
    // 验证玩家权限
    if (!$this->isPlayerTurn($player)) {
        $player->send('error', ['message' => '不是你的回合']);
        yield;
        return;
    }
    
    // 验证数据格式
    if (!$this->validateAction($data)) {
        $player->send('error', ['message' => '无效的操作']);
        yield;
        return;
    }
    
    // 执行操作
    yield from $this->executePlayerAction($player, $data);
}

private function validateAction(mixed $data): bool
{
    return is_array($data) 
        && isset($data['type']) 
        && isset($data['value']);
}
```

### 4. 状态同步

```php
private function syncGameState(): void
{
    $state = [
        'room_id' => $this->id,
        'status' => $this->status,
        'current_round' => $this->get('current_round'),
        'players' => array_map(function($p) {
            return [
                'id' => $p->getId(),
                'name' => $p->getName(),
                'score' => $p->get('score', 0),
                'ready' => $p->isReady(),
            ];
        }, $this->players)
    ];
    
    $this->broadcast('game:state_sync', $state);
}
```

## 常见问题

### Q1: 如何处理玩家断线重连？

```php
class ReconnectableRoom extends Room
{
    private array $disconnectedPlayers = [];
    
    public function handlePlayerReconnect(string $playerId, $connection): bool
    {
        if (!isset($this->disconnectedPlayers[$playerId])) {
            return false;
        }
        
        // 恢复玩家
        $playerData = $this->disconnectedPlayers[$playerId];
        $player = new Player($playerId, $connection, $playerData['name']);
        
        // 恢复玩家数据
        foreach ($playerData['data'] as $key => $value) {
            $player->set($key, $value);
        }
        
        $this->players[$playerId] = $player;
        $player->setRoom($this);
        
        // 同步游戏状态
        $player->send('reconnected', [
            'game_state' => $this->getGameState(),
            'your_data' => $player->getData()
        ]);
        
        unset($this->disconnectedPlayers[$playerId]);
        return true;
    }
    
    protected function onPlayerLeave(Player $player): void
    {
        // 保存玩家数据，允许重连
        $this->disconnectedPlayers[$player->getId()] = [
            'name' => $player->getName(),
            'data' => $player->getData(),
            'disconnect_time' => time()
        ];
        
        // 5分钟后清除
        $this->addTimer(300, function() use ($player) {
            unset($this->disconnectedPlayers[$player->getId()]);
        }, false);
    }
}
```

### Q2: 如何实现游戏录像/回放？

```php
class RecordableRoom extends Room
{
    private array $gameHistory = [];
    
    private function recordEvent(string $event, mixed $data): void
    {
        $this->gameHistory[] = [
            'timestamp' => microtime(true),
            'event' => $event,
            'data' => $data
        ];
    }
    
    protected function run(): Generator
    {
        $this->recordEvent('game:start', ['players' => $this->getPlayers()]);
        
        // 游戏逻辑...
        
        $this->recordEvent('game:end', ['result' => $this->getResult()]);
        
        // 保存录像
        yield from $this->saveReplay();
    }
    
    private function saveReplay(): Generator
    {
        $filename = "replay_{$this->id}_" . date('YmdHis') . ".json";
        file_put_contents(
            "replays/{$filename}",
            json_encode($this->gameHistory, JSON_PRETTY_PRINT)
        );
        yield;
    }
}
```

### Q3: 如何实现房间观战功能？

```php
class SpectatorRoom extends Room
{
    private array $spectators = [];
    
    public function addSpectator(Player $spectator): bool
    {
        $this->spectators[$spectator->getId()] = $spectator;
        
        // 发送当前游戏状态
        $spectator->send('spectator:joined', [
            'game_state' => $this->getGameState(),
            'players' => array_map(fn($p) => $p->toArray(), $this->players)
        ]);
        
        return true;
    }
    
    public function removeSpectator(string $spectatorId): void
    {
        unset($this->spectators[$spectatorId]);
    }
    
    public function broadcast(string $event, mixed $data = null, ?Player $except = null): void
    {
        // 广播给玩家
        parent::broadcast($event, $data, $except);
        
        // 广播给观众
        foreach ($this->spectators as $spectator) {
            $spectator->send($event, $data);
        }
    }
}
```

## 性能优化

### 1. 减少广播频率

```php
// ❌ 不好：频繁广播
protected function run(): Generator
{
    while ($this->isRunning) {
        $this->broadcast('game:update', $this->getGameState());
        yield sleep(0.1); // 每100ms广播一次
    }
}

// ✅ 好：按需广播或降低频率
protected function run(): Generator
{
    $lastBroadcast = 0;
    
    while ($this->isRunning) {
        $now = microtime(true);
        
        // 每秒广播一次
        if ($now - $lastBroadcast >= 1.0) {
            $this->broadcast('game:update', $this->getGameState());
            $lastBroadcast = $now;
        }
        
        yield sleep(0.1);
    }
}
```

### 2. 批量处理消息

```php
private array $messageQueue = [];

public function onPlayerMessage(Player $player, string $event, mixed $data): Generator
{
    // 将消息加入队列
    $this->messageQueue[] = [
        'player' => $player,
        'event' => $event,
        'data' => $data
    ];
    yield;
}

private function processMessageBatch(): void
{
    $batch = array_splice($this->messageQueue, 0);
    
    foreach ($batch as $message) {
        // 批量处理
        $this->handleMessage($message);
    }
    
    // 一次性广播结果
    $this->broadcast('batch:processed', [
        'count' => count($batch)
    ]);
}
```

### 3. 内存管理

```php
protected function onDestroy(): Generator
{
    // 清理大对象
    unset($this->data['large_data']);
    
    // 清空数组
    $this->players = [];
    $this->timerIds = [];
    
    // 强制垃圾回收（生产环境慎用）
    if (function_exists('gc_collect_cycles')) {
        gc_collect_cycles();
    }
    
    yield;
}
```

### 4. 使用对象池

```php
class PlayerPool
{
    private array $pool = [];
    
    public function acquire(string $id, $connection, string $name): Player
    {
        if (!empty($this->pool)) {
            $player = array_pop($this->pool);
            // 重置玩家状态
            return $player;
        }
        
        return new Player($id, $connection, $name);
    }
    
    public function release(Player $player): void
    {
        // 清理玩家数据
        $player->setRoom(null);
        $this->pool[] = $player;
    }
}
```

## 调试技巧

### 1. 启用详细日志

```php
protected function run(): Generator
{
    $this->debug("游戏开始，玩家数: {$this->getPlayerCount()}");
    
    // 游戏逻辑...
    
    $this->debug("游戏结束");
}

private function debug(string $message): void
{
    if ($this->config['debug'] ?? false) {
        $timestamp = date('Y-m-d H:i:s');
        echo "[{$timestamp}] Room {$this->id}: {$message}\n";
    }
}
```

### 2. 性能监控

```php
protected function run(): Generator
{
    $startTime = microtime(true);
    
    // 游戏逻辑...
    
    $duration = microtime(true) - $startTime;
    $this->debug("游戏耗时: {$duration}秒");
    
    // 记录到监控系统
    $this->recordMetric('game_duration', $duration);
}
```

---

更多信息请参考 [README.md](../README.md)

