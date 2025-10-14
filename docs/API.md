# API 参考文档

## 目录

- [Room 类](#room-类)
- [Player 类](#player-类)
- [RoomManager 类](#roommanager-类)
- [GameServer 类](#gameserver-类)

---

## Room 类

`PfinalClub\AsyncioGamekit\Room`

游戏房间基类，所有游戏房间都应该继承此类。

### 构造函数

```php
public function __construct(string $id, array $config = [])
```

**参数：**
- `$id` - 房间ID
- `$config` - 房间配置数组

### 配置方法

#### getDefaultConfig()

```php
protected function getDefaultConfig(): array
```

返回默认配置，子类可重写。

**默认配置：**
```php
[
    'max_players' => 4,      // 最大玩家数
    'min_players' => 2,      // 最小玩家数
    'auto_start' => false,   // 是否自动开始
]
```

### 生命周期钩子

#### onCreate()

```php
protected function onCreate(): Generator
```

房间创建时调用，用于初始化房间数据。

**示例：**
```php
protected function onCreate(): Generator
{
    $this->set('created_at', time());
    $this->broadcast('room:created', ['message' => '房间已创建']);
    yield;
}
```

#### onStart()

```php
protected function onStart(): Generator
```

游戏开始前调用，用于游戏准备工作。

**示例：**
```php
protected function onStart(): Generator
{
    $this->broadcast('game:start', ['message' => '游戏即将开始']);
    yield sleep(3); // 倒计时3秒
}
```

#### run()

```php
abstract protected function run(): Generator
```

游戏主循环，**必须实现**。

**示例：**
```php
protected function run(): Generator
{
    for ($round = 1; $round <= 10; $round++) {
        $this->broadcast('game:round', ['round' => $round]);
        yield sleep(5);
    }
    yield from $this->destroy();
}
```

#### onDestroy()

```php
protected function onDestroy(): Generator
```

房间销毁时调用，用于清理资源。

**示例：**
```php
protected function onDestroy(): Generator
{
    // 保存游戏记录
    yield from $this->saveGameRecord();
    
    // 清理数据
    $this->data = [];
    yield;
}
```

#### onPlayerJoin()

```php
protected function onPlayerJoin(Player $player): void
```

玩家加入时调用（同步方法）。

**参数：**
- `$player` - 加入的玩家对象

#### onPlayerLeave()

```php
protected function onPlayerLeave(Player $player): void
```

玩家离开时调用（同步方法）。

**参数：**
- `$player` - 离开的玩家对象

#### onPlayerMessage()

```php
public function onPlayerMessage(Player $player, string $event, mixed $data): Generator
```

处理玩家消息（异步方法）。

**参数：**
- `$player` - 发送消息的玩家
- `$event` - 事件名称
- `$data` - 消息数据

**示例：**
```php
public function onPlayerMessage(Player $player, string $event, mixed $data): Generator
{
    match ($event) {
        'action' => $this->handleAction($player, $data),
        'chat' => $this->handleChat($player, $data),
        default => null,
    };
    yield;
}
```

### 玩家管理

#### addPlayer()

```php
public function addPlayer(Player $player): bool
```

添加玩家到房间。

**返回：**
- `bool` - 成功返回 true，失败返回 false

#### removePlayer()

```php
public function removePlayer(string $playerId): bool
```

从房间移除玩家。

**参数：**
- `$playerId` - 玩家ID

**返回：**
- `bool` - 成功返回 true，失败返回 false

#### getPlayer()

```php
public function getPlayer(string $playerId): ?Player
```

获取玩家对象。

**返回：**
- `Player|null` - 玩家对象，不存在返回 null

#### getPlayers()

```php
public function getPlayers(): array<string, Player>
```

获取所有玩家。

**返回：**
- `array` - 玩家数组，键为玩家ID

#### getPlayerCount()

```php
public function getPlayerCount(): int
```

获取玩家数量。

### 消息广播

#### broadcast()

```php
public function broadcast(string $event, mixed $data = null, ?Player $except = null): void
```

广播消息给房间内所有玩家。

**参数：**
- `$event` - 事件名称
- `$data` - 数据（可选）
- `$except` - 排除的玩家（可选）

**示例：**
```php
// 广播给所有人
$this->broadcast('game:update', ['score' => 100]);

// 广播给除了某玩家外的所有人
$this->broadcast('player:action', $data, $currentPlayer);
```

#### broadcastAsync()

```php
public function broadcastAsync(string $event, mixed $data = null, float $delay = 0): Generator
```

异步广播消息（可延迟）。

**参数：**
- `$event` - 事件名称
- `$data` - 数据（可选）
- `$delay` - 延迟秒数（可选）

**示例：**
```php
// 延迟3秒后广播
yield from $this->broadcastAsync('game:start', null, 3.0);
```

### 定时器

#### addTimer()

```php
protected function addTimer(float $interval, callable $callback, bool $repeat = false): int
```

添加定时任务。

**参数：**
- `$interval` - 时间间隔（秒）
- `$callback` - 回调函数
- `$repeat` - 是否重复执行

**返回：**
- `int` - 定时器ID

**示例：**
```php
// 一次性定时器
$timerId = $this->addTimer(5.0, function() {
    echo "5秒后执行\n";
}, false);

// 重复定时器
$timerId = $this->addTimer(1.0, function() {
    echo "每秒执行\n";
}, true);
```

#### removeTimer()

```php
protected function removeTimer(int $timerId): void
```

移除定时任务。

**参数：**
- `$timerId` - 定时器ID

### 辅助方法

#### delay()

```php
protected function delay(float $seconds): Generator
```

延迟执行。

**参数：**
- `$seconds` - 延迟秒数

**示例：**
```php
yield from $this->delay(5.0); // 延迟5秒
```

#### start()

```php
public function start(): Generator
```

启动房间游戏逻辑。

#### destroy()

```php
public function destroy(): Generator
```

销毁房间。

#### canStart()

```php
public function canStart(): bool
```

检查是否可以开始游戏。

### 状态管理

#### getId()

```php
public function getId(): string
```

获取房间ID。

#### getStatus()

```php
public function getStatus(): string
```

获取房间状态。

**可能的值：**
- `waiting` - 等待中
- `running` - 运行中
- `finished` - 已结束

#### set()

```php
public function set(string $key, mixed $value): void
```

设置自定义数据。

#### get()

```php
public function get(string $key, mixed $default = null): mixed
```

获取自定义数据。

#### toArray()

```php
public function toArray(): array
```

转换为数组表示。

---

## Player 类

`PfinalClub\AsyncioGamekit\Player`

玩家类，封装玩家通信和状态管理。

### 构造函数

```php
public function __construct(string $id, mixed $connection = null, ?string $name = null)
```

**参数：**
- `$id` - 玩家ID
- `$connection` - 连接对象（如 Workerman Connection）
- `$name` - 玩家名称（可选）

### 基本方法

#### getId()

```php
public function getId(): string
```

获取玩家ID。

#### getName()

```php
public function getName(): string
```

获取玩家名称。

#### setName()

```php
public function setName(string $name): void
```

设置玩家名称。

#### getConnection()

```php
public function getConnection(): mixed
```

获取连接对象。

### 消息发送

#### send()

```php
public function send(string $event, mixed $data = null): void
```

发送消息给玩家。

**参数：**
- `$event` - 事件名称
- `$data` - 数据（可选）

**示例：**
```php
$player->send('notification', ['message' => 'Your turn']);
```

### 房间管理

#### setRoom()

```php
public function setRoom(?Room $room): void
```

设置所在房间。

#### getRoom()

```php
public function getRoom(): ?Room
```

获取所在房间。

### 数据管理

#### set()

```php
public function set(string $key, mixed $value): void
```

设置自定义数据。

#### get()

```php
public function get(string $key, mixed $default = null): mixed
```

获取自定义数据。

#### has()

```php
public function has(string $key): bool
```

检查是否有指定数据。

#### getData()

```php
public function getData(): array
```

获取所有自定义数据。

### 状态管理

#### setReady()

```php
public function setReady(bool $ready): void
```

设置准备状态。

#### isReady()

```php
public function isReady(): bool
```

是否已准备。

#### toArray()

```php
public function toArray(): array
```

转换为数组表示。

---

## RoomManager 类

`PfinalClub\AsyncioGamekit\RoomManager`

房间管理器，管理多个游戏房间。

### 房间管理

#### createRoom()

```php
public function createRoom(string $roomClass, ?string $roomId = null, array $config = []): Room
```

创建房间。

**参数：**
- `$roomClass` - 房间类名
- `$roomId` - 房间ID（可选，不指定则自动生成）
- `$config` - 房间配置

**返回：**
- `Room` - 创建的房间对象

#### getRoom()

```php
public function getRoom(string $roomId): ?Room
```

获取房间。

#### getRooms()

```php
public function getRooms(): array<string, Room>
```

获取所有房间。

#### removeRoom()

```php
public function removeRoom(string $roomId): Generator
```

删除房间。

### 玩家管理

#### joinRoom()

```php
public function joinRoom(Player $player, string $roomId): bool
```

玩家加入房间。

#### leaveRoom()

```php
public function leaveRoom(Player $player): bool
```

玩家离开房间。

#### getPlayerRoom()

```php
public function getPlayerRoom(Player $player): ?Room
```

获取玩家所在房间。

### 匹配功能

#### findAvailableRoom()

```php
public function findAvailableRoom(string $roomClass): ?Room
```

查找可用房间。

#### quickMatch()

```php
public function quickMatch(Player $player, string $roomClass, array $config = []): Room
```

快速匹配（自动创建或加入房间）。

### 统计信息

#### getStats()

```php
public function getStats(): array
```

获取房间统计信息。

**返回示例：**
```php
[
    'total_rooms' => 5,
    'total_players' => 12,
    'rooms_by_status' => [
        'waiting' => 2,
        'running' => 3,
        'finished' => 0,
    ]
]
```

---

## GameServer 类

`PfinalClub\AsyncioGamekit\GameServer`

WebSocket 游戏服务器。

### 构造函数

```php
public function __construct(string $host = '0.0.0.0', int $port = 2345, array $config = [])
```

**参数：**
- `$host` - 监听地址
- `$port` - 监听端口
- `$config` - 服务器配置

**配置选项：**
```php
[
    'name' => 'GameServer',    // 服务器名称
    'count' => 4,              // Worker 进程数
    'protocol' => 'websocket', // 协议类型
]
```

### 方法

#### getRoomManager()

```php
public function getRoomManager(): RoomManager
```

获取房间管理器。

#### run()

```php
public function run(): void
```

运行服务器。

### 系统事件

客户端可以发送的系统事件：

| 事件 | 数据格式 | 说明 |
|------|---------|------|
| `set_name` | `{name: "string"}` | 设置玩家名称 |
| `create_room` | `{room_class: "string", config: {}}` | 创建房间 |
| `join_room` | `{room_id: "string"}` | 加入房间 |
| `leave_room` | `{}` | 离开房间 |
| `quick_match` | `{room_class: "string", config: {}}` | 快速匹配 |
| `get_rooms` | `{}` | 获取房间列表 |
| `get_stats` | `{}` | 获取统计信息 |

---

## 类型定义

### 消息格式

客户端和服务器之间的消息格式：

```typescript
interface Message {
    event: string;
    data?: any;
    timestamp?: number;
}
```

### 房间状态

```php
const ROOM_STATUS_WAITING = 'waiting';
const ROOM_STATUS_RUNNING = 'running';
const ROOM_STATUS_FINISHED = 'finished';
```

---

更多示例请参考 [examples/](../examples/) 目录。

