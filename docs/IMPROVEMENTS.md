# 项目改进文档

本文档记录了对 pfinal-asyncio-gamekit 项目的改进内容。

## 改进概览

### 1. 单元测试框架 ✅

新增了完整的单元测试框架，确保代码质量和稳定性。

#### 添加的测试文件：
- `tests/PlayerTest.php` - Player 类测试
- `tests/RoomTest.php` - Room 类测试
- `tests/RoomManagerTest.php` - RoomManager 类测试
- `phpunit.xml` - PHPUnit 配置文件

#### 运行测试：
```bash
composer require --dev phpunit/phpunit
./vendor/bin/phpunit
```

#### 测试覆盖：
- Player 类：数据管理、准备状态、序列化
- Room 类：玩家管理、生命周期、配置
- RoomManager 类：房间创建、加入/离开、快速匹配

---

### 2. 错误处理机制 ✅

增强了异常处理系统，提供更好的错误信息和恢复能力。

#### 新增异常类：
- `Exceptions/GameException.php` - 基础异常类
- `Exceptions/RoomException.php` - 房间相关异常
- `Exceptions/PlayerException.php` - 玩家相关异常
- `Exceptions/ServerException.php` - 服务器相关异常

#### 特性：
- 结构化异常信息（带上下文）
- 异常代码分类
- 静态工厂方法快速创建异常
- 集成到核心类中

#### 使用示例：
```php
try {
    $room->addPlayer($player);
} catch (RoomException $e) {
    // 获取详细错误信息
    $code = $e->getCode();
    $context = $e->getContext();
    $message = $e->getMessage();
    
    // 发送给客户端
    $player->send('error', [
        'message' => $message,
        'code' => $code,
        'context' => $context
    ]);
}
```

---

### 3. 日志系统 ✅

集成了完善的日志记录系统，支持多种输出方式和日志级别。

#### 新增文件：
- `Logger/LogLevel.php` - 日志级别定义
- `Logger/Logger.php` - 核心日志类
- `Logger/LogHandlerInterface.php` - 处理器接口
- `Logger/ConsoleLogHandler.php` - 控制台输出
- `Logger/FileLogHandler.php` - 文件输出
- `Logger/LoggerFactory.php` - 日志工厂

#### 日志级别：
- DEBUG - 调试信息
- INFO - 一般信息
- NOTICE - 通知
- WARNING - 警告
- ERROR - 错误
- CRITICAL - 严重错误
- ALERT - 警报
- EMERGENCY - 紧急情况

#### 配置示例：
```php
use PfinalClub\AsyncioGamekit\Logger\LoggerFactory;
use PfinalClub\AsyncioGamekit\Logger\LogLevel;

LoggerFactory::configure([
    'min_level' => LogLevel::INFO,
    'console' => [
        'enabled' => true,
        'color' => true,
    ],
    'file' => [
        'enabled' => true,
        'path' => 'logs/game.log',
        'max_size' => 10 * 1024 * 1024, // 10MB
        'max_files' => 5,
    ],
]);

// 使用日志
LoggerFactory::info('Game started', ['room_id' => 'room_001']);
LoggerFactory::error('Game error: {message}', ['message' => 'Connection lost']);
```

---

### 4. 房间状态持久化 ✅

添加了房间状态持久化支持，可以在服务器重启后恢复游戏状态。

#### 新增文件：
- `Persistence/PersistenceAdapterInterface.php` - 持久化接口
- `Persistence/FileAdapter.php` - 文件存储适配器
- `Persistence/RedisAdapter.php` - Redis 存储适配器
- `Persistence/MemoryAdapter.php` - 内存存储（测试用）
- `Persistence/RoomStateManager.php` - 房间状态管理器

#### 支持的适配器：
1. **FileAdapter** - 使用文件存储，无需额外依赖
2. **RedisAdapter** - 使用 Redis，高性能
3. **MemoryAdapter** - 内存存储，测试专用

#### 使用示例：

**文件存储：**
```php
use PfinalClub\AsyncioGamekit\Persistence\FileAdapter;
use PfinalClub\AsyncioGamekit\Persistence\RoomStateManager;

$adapter = new FileAdapter('storage/game');
$stateManager = new RoomStateManager($adapter);

// 保存房间状态
$stateManager->saveRoomState($room);

// 恢复房间状态
$state = $stateManager->getRoomState('room_001');
```

**Redis 存储：**
```php
use PfinalClub\AsyncioGamekit\Persistence\RedisAdapter;

$adapter = RedisAdapter::create(
    host: '127.0.0.1',
    port: 6379,
    password: '',
    database: 0,
    prefix: 'gamekit:'
);

$stateManager = new RoomStateManager($adapter);
```

#### 功能：
- 保存/恢复房间状态
- 玩家会话管理
- 游戏统计数据持久化
- 自动过期机制

---

### 5. 负载均衡策略 ✅

实现了多进程负载均衡，支持多种分配策略。

#### 新增文件：
- `LoadBalance/LoadBalancerInterface.php` - 负载均衡器接口
- `LoadBalance/RoundRobinBalancer.php` - 轮询策略
- `LoadBalance/LeastConnectionsBalancer.php` - 最少连接策略
- `LoadBalance/WeightedBalancer.php` - 加权策略
- `LoadBalance/RoomDistributor.php` - 房间分配器

#### 负载均衡策略：

**1. 轮询（Round Robin）**
```php
use PfinalClub\AsyncioGamekit\LoadBalance\RoundRobinBalancer;
use PfinalClub\AsyncioGamekit\LoadBalance\RoomDistributor;

$balancer = new RoundRobinBalancer();
$distributor = new RoomDistributor($balancer);

// 注册工作进程
$distributor->registerWorker(1);
$distributor->registerWorker(2);
$distributor->registerWorker(3);

// 分配房间
$workerId = $distributor->assignRoom('room_001');
```

**2. 最少连接（Least Connections）**
```php
use PfinalClub\AsyncioGamekit\LoadBalance\LeastConnectionsBalancer;

$balancer = new LeastConnectionsBalancer();
$distributor = new RoomDistributor($balancer);

// 注册时提供连接数信息
$distributor->registerWorker(1, ['connections' => 10]);
$distributor->registerWorker(2, ['connections' => 5]);
$distributor->registerWorker(3, ['connections' => 15]);

// 会选择连接数最少的进程（worker 2）
$workerId = $distributor->assignRoom('room_001');
```

**3. 加权（Weighted）**
```php
use PfinalClub\AsyncioGamekit\LoadBalance\WeightedBalancer;

// 配置权重（权重越大，分配越多）
$balancer = new WeightedBalancer([
    1 => 1,  // worker 1: 权重 1
    2 => 2,  // worker 2: 权重 2
    3 => 3,  // worker 3: 权重 3
]);

$distributor = new RoomDistributor($balancer);
```

#### 持久化房间分配：
```php
use PfinalClub\AsyncioGamekit\Persistence\RedisAdapter;

$storage = RedisAdapter::create();
$distributor = new RoomDistributor($balancer, $storage);

// 房间分配信息会自动保存到 Redis
$workerId = $distributor->assignRoom('room_001');

// 下次可以直接获取
$workerId = $distributor->getRoomWorker('room_001');
```

---

## 升级指南

### 从旧版本升级

1. **安装 PHPUnit（可选）**
```bash
composer require --dev phpunit/phpunit
```

2. **配置日志系统（推荐）**
```php
// 在 GameServer 启动前配置
use PfinalClub\AsyncioGamekit\Logger\LoggerFactory;

LoggerFactory::configure([
    'min_level' => 'info',
    'console' => ['enabled' => true],
    'file' => [
        'enabled' => true,
        'path' => 'logs/game.log',
    ],
]);
```

3. **添加异常处理（推荐）**
```php
try {
    $room->addPlayer($player);
} catch (RoomException $e) {
    // 处理异常
    $player->send('error', $e->toArray());
}
```

4. **启用持久化（可选）**
```php
use PfinalClub\AsyncioGamekit\Persistence\FileAdapter;
use PfinalClub\AsyncioGamekit\Persistence\RoomStateManager;

$adapter = new FileAdapter();
$stateManager = new RoomStateManager($adapter);

// 在适当时机保存状态
$stateManager->saveRoomState($room);
```

---

## 最佳实践

### 1. 日志记录
```php
// 在关键位置记录日志
LoggerFactory::info("Player {player_name} joined room {room_id}", [
    'player_name' => $player->getName(),
    'room_id' => $room->getId(),
]);
```

### 2. 异常处理
```php
// 捕获并记录异常
try {
    // 游戏逻辑
} catch (GameException $e) {
    LoggerFactory::error("Game error: {message}", [
        'message' => $e->getMessage(),
        'context' => $e->getContext(),
    ]);
    throw $e;
}
```

### 3. 状态持久化
```php
// 定期保存重要状态
$this->addTimer(60, function() use ($stateManager, $room) {
    $stateManager->saveRoomState($room);
}, true);
```

### 4. 负载均衡
```php
// 根据服务器负载选择策略
if ($highLoad) {
    $balancer = new LeastConnectionsBalancer();
} else {
    $balancer = new RoundRobinBalancer();
}
```

---

## 性能考虑

### 日志系统
- 生产环境建议设置最小日志级别为 INFO 或 WARNING
- 使用文件日志时注意磁盘 I/O
- 考虑使用日志轮转避免文件过大

### 持久化
- Redis 适配器性能最佳，推荐生产环境使用
- 文件适配器适合小规模部署
- 合理设置过期时间，避免数据堆积

### 负载均衡
- 最少连接策略适合长连接场景
- 加权策略适合服务器配置不同的情况
- 定期更新工作进程状态信息

---

## 测试

运行全部测试：
```bash
./vendor/bin/phpunit
```

运行特定测试：
```bash
./vendor/bin/phpunit tests/RoomTest.php
```

生成覆盖率报告：
```bash
./vendor/bin/phpunit --coverage-html coverage/
```

---

## 贡献

欢迎提交 Issue 和 Pull Request！

改进建议：
- 性能优化
- 更多测试用例
- 文档完善
- Bug 修复

---

Made with ❤️ by PFinal Club

