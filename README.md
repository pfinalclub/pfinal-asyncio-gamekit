# pfinal-asyncio-gamekit

[![Version](https://img.shields.io/badge/version-3.0.1-blue.svg)](https://github.com/pfinalclub/pfinal-asyncio-gamekit)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

> 基于 [pfinal-asyncio](https://github.com/pfinalclub/pfinal-asyncio) 的轻量级异步游戏逻辑框架

一个面向 Workerman + pfinal-asyncio 的异步游戏框架，让你用 `async/await` 编排游戏逻辑，就像写故事剧本一样。

## ✨ 特性

### 核心功能

- 🎮 **Room 基类** - 带完整生命周期钩子的异步房间管理
- 👥 **Player 通信封装** - 简单易用的玩家消息收发
- 📢 **异步广播机制** - 支持即时和延迟广播
- ⏰ **定时事件系统** - 灵活的定时器和延迟任务
- 🔄 **房间生命周期** - onCreate、onStart、run、onDestroy 完整钩子
- 🎯 **RoomManager** - 多房间管理和快速匹配
- 🌐 **GameServer** - 开箱即用的 WebSocket 游戏服务器

### 高级功能 🆕

- 🧪 **单元测试** - 完整的 PHPUnit 测试框架
- 🚨 **异常处理** - 结构化异常系统，带上下文信息
- 📝 **日志系统** - 多级别日志，支持控制台和文件输出
- 💾 **状态持久化** - 支持 Redis、文件等多种存储方式
- ⚖️ **负载均衡** - 多进程房间分配，支持轮询、最少连接等策略

### 框架定位

专为 **小型多人游戏** 和 **实时对战游戏** 设计，适用于：

- 🃏 卡牌游戏
- 🎲 棋牌游戏
- 🎯 答题游戏
- 🏁 实时对战游戏
- 🎮 回合制游戏

## 📦 安装

```bash
composer require pfinalclub/asyncio-gamekit
```

## 📋 要求

- PHP >= 8.1
- pfinalclub/asyncio >= 3.0
- workerman/workerman >= 4.1 或 >= 5.0

> **注意**: 如果您的项目已安装 `workerman/workerman v5.x`，请确保 `pfinalclub/asyncio` 也支持 Workerman v5。如果遇到依赖冲突，请使用 `composer require pfinalclub/asyncio-gamekit --with-all-dependencies` 安装。

## 🚀 快速开始

### 1. 创建你的第一个游戏房间

```php
<?php
use PfinalClub\AsyncioGamekit\Room\Room;
use PfinalClub\AsyncioGamekit\Player;
use function PfinalClub\Asyncio\{run, sleep};

class MyGameRoom extends Room
{
    protected function run(): mixed
    {
        // 游戏开始
        $this->broadcast('game:start', ['message' => '游戏开始！']);
        
        // 游戏主循环
        for ($round = 1; $round <= 3; $round++) {
            $this->broadcast('game:round', ['round' => $round]);
            sleep(5); // 每回合5秒
        }
        
        // 游戏结束
        $this->broadcast('game:end', ['message' => '游戏结束！']);
        $this->destroy();
    }
}

// 运行游戏
function main(): mixed {
    $room = new MyGameRoom('room_001');
    
    $player1 = new Player('p1', null, 'Alice');
    $player2 = new Player('p2', null, 'Bob');
    
    $room->addPlayer($player1);
    $room->addPlayer($player2);
    
    $room->start();
}

run(main(...));
```

### 2. 完整的 WebSocket 游戏服务器

```php
<?php
use PfinalClub\AsyncioGamekit\GameServer;

$server = new GameServer('0.0.0.0', 2345, [
    'name' => 'MyGameServer',
    'count' => 4,
]);

$server->run();
```

客户端连接：

```javascript
const ws = new WebSocket('ws://localhost:2345');

ws.onopen = () => {
    // 设置玩家名称
    ws.send(JSON.stringify({
        event: 'set_name',
        data: { name: 'Alice' }
    }));
    
    // 快速匹配
    ws.send(JSON.stringify({
        event: 'quick_match',
        data: { room_class: 'MyGameRoom' }
    }));
};

ws.onmessage = (event) => {
    const message = JSON.parse(event.data);
    console.log('收到消息:', message);
};
```

## 📚 核心 API

### Room 类

房间是游戏逻辑的核心容器，提供完整的生命周期管理。

#### 配置选项

```php
protected function getDefaultConfig(): array
{
    return [
        'max_players' => 4,      // 最大玩家数
        'min_players' => 2,      // 最小玩家数
        'auto_start' => false,   // 是否自动开始
    ];
}
```

#### 生命周期钩子

```php
// 房间创建时（异步）
protected function onCreate(): mixed
{
    $this->broadcast('room:created', ['message' => '房间已创建']);
}

// 游戏开始时（异步）
protected function onStart(): mixed
{
    $this->broadcast('game:start', ['message' => '游戏即将开始']);
    sleep(3);
}

// 游戏主循环（必须实现）
abstract protected function run(): mixed;

// 房间销毁时（异步）
protected function onDestroy(): mixed
{
    $this->broadcast('room:destroyed', ['message' => '房间已销毁']);
}

// 玩家加入时（同步）
protected function onPlayerJoin(Player $player): void
{
    echo "{$player->getName()} 加入房间\n";
}

// 玩家离开时（同步）
protected function onPlayerLeave(Player $player): void
{
    echo "{$player->getName()} 离开房间\n";
}

// 处理玩家消息（异步）
public function onPlayerMessage(Player $player, string $event, mixed $data): mixed
{
    if ($event === 'action') {
        // 处理玩家动作
    }
}
```

#### 常用方法

```php
// 广播消息
$this->broadcast('event_name', $data);
$this->broadcast('event_name', $data, $exceptPlayer); // 排除某玩家

// 异步广播（延迟）
$this->broadcastAsync('event_name', $data, 2.0); // 延迟2秒

// 延迟执行
$this->delay(5.0); // 延迟5秒

// 添加定时器
$timerId = $this->addTimer(1.0, function() {
    echo "每秒执行一次\n";
}, true); // true = 重复执行

// 移除定时器
$this->removeTimer($timerId);

// 玩家管理
$this->addPlayer($player);
$this->removePlayer($playerId);
$player = $this->getPlayer($playerId);
$players = $this->getPlayers();
$count = $this->getPlayerCount();

// 数据存储
$this->set('key', 'value');
$value = $this->get('key', 'default');

// 房间状态
$status = $this->getStatus(); // waiting, running, finished
$canStart = $this->canStart();
```

### Player 类

封装玩家通信和状态管理。

```php
// 创建玩家
$player = new Player('player_id', $connection, 'PlayerName');

// 发送消息
$player->send('event_name', ['data' => 'value']);

// 数据管理
$player->set('score', 100);
$score = $player->get('score', 0);
$hasScore = $player->has('score');

// 准备状态
$player->setReady(true);
$isReady = $player->isReady();

// 获取信息
$id = $player->getId();
$name = $player->getName();
$room = $player->getRoom();
$array = $player->toArray();
```

### RoomManager 类

管理多个游戏房间。

```php
$manager = new RoomManager();

// 创建房间
$room = $manager->createRoom(MyGameRoom::class, 'room_id', [
    'max_players' => 4
]);

// 玩家加入/离开房间
$manager->joinRoom($player, 'room_id');
$manager->leaveRoom($player);

// 获取房间
$room = $manager->getRoom('room_id');
$rooms = $manager->getRooms();
$playerRoom = $manager->getPlayerRoom($player);

// 快速匹配（自动创建或加入房间）
$room = $manager->quickMatch($player, MyGameRoom::class, [
    'max_players' => 4
]);

// 删除房间
yield from $manager->removeRoom('room_id');

// 统计信息
$stats = $manager->getStats();
```

### GameServer 类

WebSocket 游戏服务器。

```php
$server = new GameServer('0.0.0.0', 2345, [
    'name' => 'GameServer',
    'count' => 4,           // Worker 进程数
    'protocol' => 'websocket',
]);

$server->run();
```

#### 内置系统事件

客户端可以发送以下系统事件：

| 事件 | 说明 | 数据 |
|------|------|------|
| `set_name` | 设置玩家名称 | `{name: "PlayerName"}` |
| `create_room` | 创建房间 | `{room_class: "ClassName", config: {...}}` |
| `join_room` | 加入房间 | `{room_id: "room_id"}` |
| `leave_room` | 离开房间 | `{}` |
| `quick_match` | 快速匹配 | `{room_class: "ClassName", config: {...}}` |
| `get_rooms` | 获取房间列表 | `{}` |
| `get_stats` | 获取统计信息 | `{}` |

## 🎯 示例

### 示例 1：简单倒计时游戏

运行：
```bash
php examples/SimpleGame.php
```

### 示例 2：卡牌游戏

运行：
```bash
php examples/CardGame.php
```

### 示例 3：WebSocket 猜数字游戏

启动服务器：
```bash
php examples/WebSocketServer.php
```

然后在浏览器中打开 `examples/client.html` 连接游戏服务器。

### 示例 4：高级游戏（新特性展示）🆕

展示日志、异常处理、持久化等新特性：
```bash
php examples/AdvancedGame.php
```

## 🆕 新特性使用

### 日志系统

```php
use PfinalClub\AsyncioGamekit\Logger\LoggerFactory;
use PfinalClub\AsyncioGamekit\Logger\LogLevel;

// 配置日志
LoggerFactory::configure([
    'min_level' => LogLevel::INFO,
    'console' => ['enabled' => true, 'color' => true],
    'file' => [
        'enabled' => true,
        'path' => 'logs/game.log',
        'max_size' => 10 * 1024 * 1024,
    ],
]);

// 记录日志
LoggerFactory::info('Game started', ['room_id' => 'room_001']);
LoggerFactory::error('Error: {message}', ['message' => 'Connection lost']);
```

### 异常处理

```php
use PfinalClub\AsyncioGamekit\Exceptions\RoomException;

try {
    $room->addPlayer($player);
} catch (RoomException $e) {
    // 获取结构化异常信息
    $player->send('error', [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'context' => $e->getContext()
    ]);
}
```

### 状态持久化

```php
use PfinalClub\AsyncioGamekit\Persistence\{FileAdapter, RedisAdapter, RoomStateManager};

// 使用文件存储
$adapter = new FileAdapter('storage/game');
$stateManager = new RoomStateManager($adapter);

// 或使用 Redis（推荐）
$adapter = RedisAdapter::create('127.0.0.1', 6379);
$stateManager = new RoomStateManager($adapter);

// 保存和恢复房间状态
$stateManager->saveRoomState($room);
$state = $stateManager->getRoomState('room_001');
```

### 负载均衡

```php
use PfinalClub\AsyncioGamekit\LoadBalance\{
    RoomDistributor,
    LeastConnectionsBalancer
};

// 创建负载均衡器
$balancer = new LeastConnectionsBalancer();
$distributor = new RoomDistributor($balancer);

// 注册工作进程
$distributor->registerWorker(1, ['connections' => 10]);
$distributor->registerWorker(2, ['connections' => 5]);

// 分配房间（会选择连接数最少的进程）
$workerId = $distributor->assignRoom('room_001');
```

### 单元测试

```bash
# 安装 PHPUnit
composer require --dev phpunit/phpunit

# 运行测试
./vendor/bin/phpunit

# 运行特定测试
./vendor/bin/phpunit tests/RoomTest.php
```

## 🏗️ 高级用法

### 自定义定时任务

```php
protected function run(): Generator
{
    // 添加定时器
    $timerId = $this->addTimer(1.0, function() {
        $elapsed = time() - $this->get('start_time');
        $this->broadcast('game:timer', ['elapsed' => $elapsed]);
    }, true);
    
    $this->set('start_time', time());
    
    // 游戏逻辑
    sleep(60);
    
    // 清理定时器
    $this->removeTimer($timerId);
}
```

### 异步任务编排

```php
use function PfinalClub\Asyncio\{create_task, gather};

protected function run(): mixed
{
    // 并发执行多个任务
    $task1 = create_task(fn() => $this->taskA());
    $task2 = create_task(fn() => $this->taskB());
    
    $results = gather($task1, $task2);
    
    // 继续游戏逻辑...
}

private function taskA(): mixed
{
    sleep(2);
    return 'Result A';
}

private function taskB(): mixed
{
    sleep(1);
    return 'Result B';
}
```

### 超时控制

```php
use function PfinalClub\Asyncio\wait_for;
use PfinalClub\Asyncio\TimeoutException;

protected function run(): mixed
{
    try {
        // 等待玩家响应，最多10秒
        $result = wait_for(fn() => $this->waitForPlayerAction(), 10.0);
    } catch (TimeoutException $e) {
        // 超时处理
        $this->broadcast('game:timeout', ['message' => '超时！']);
    }
}
```

### 房间间通信

```php
// 通过 RoomManager 实现房间间通信
$roomManager = new RoomManager();

$room1 = $roomManager->getRoom('room_001');
$room2 = $roomManager->getRoom('room_002');

// Room1 广播消息给 Room2 的玩家
foreach ($room2->getPlayers() as $player) {
    $player->send('cross_room_message', ['from' => $room1->getId()]);
}
```

## 🎮 完整游戏示例

查看 `examples/` 目录了解更多完整示例：

- **SimpleGame.php** - 简单的倒计时游戏
- **CardGame.php** - 回合制卡牌游戏
- **WebSocketServer.php** - 完整的 WebSocket 游戏服务器
- **AdvancedGame.php** 🆕 - 展示新特性的高级示例
- **client.html** - 网页游戏客户端

## 🔧 配置建议

### 生产环境配置

```php
$server = new GameServer('0.0.0.0', 2345, [
    'name' => 'ProductionGameServer',
    'count' => 8,  // 根据 CPU 核心数调整
]);
```

### 调试模式

```php
// 开启 Workerman 调试模式
use Workerman\Worker;

Worker::$daemonize = false;
Worker::$stdoutFile = '/tmp/workerman.log';
```

## 📚 文档

### 用户文档
- [API 文档](docs/API.md) - 完整的 API 参考
- [使用指南](docs/GUIDE.md) - 详细的使用教程
- [改进说明](docs/IMPROVEMENTS.md) 🆕 - 新特性详解
- [安装说明](INSTALL.md) - 安装和配置

### 运维文档
- [生产部署](docs/PRODUCTION_GUIDE.md) 🆕 - 生产环境部署指南
- [测试指南](docs/TESTING.md) 🆕 - 单元测试最佳实践

### 项目文档
- [项目结构](PROJECT_STRUCTURE.md) - 代码组织说明
- [更新日志](CHANGELOG.md) - 版本历史
- [贡献指南](CONTRIBUTING.md) - 如何参与贡献

## 📖 与 Python asyncio 对比

| 功能 | Python asyncio | pfinal-asyncio-gamekit |
|------|----------------|------------------------|
| 协程定义 | `async def` | `function(): mixed` |
| 等待协程 | `await expr` | `await(expr)` |
| 睡眠 | `await asyncio.sleep(1)` | `sleep(1)` |
| 并发任务 | `asyncio.gather()` | `gather()` |
| 创建任务 | `asyncio.create_task()` | `create_task(fn())` |
| 事件循环 | `asyncio.run()` | `run()` |

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 📄 许可证

MIT License

## 🔗 相关链接

- [pfinal-asyncio](https://github.com/pfinalclub/pfinal-asyncio) - 底层异步框架
- [Workerman](https://www.workerman.net/) - PHP 异步框架
- [Python asyncio](https://docs.python.org/3/library/asyncio.html) - 参考设计

## 💡 示例截图

WebSocket 客户端示例：

![客户端示例](https://via.placeholder.com/800x400?text=Game+Client+Example)

## 🎓 教程

更多教程和文档正在编写中...

---

Made with ❤️ by PFinal Club
