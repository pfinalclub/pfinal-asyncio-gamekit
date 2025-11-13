# v2 到 v3 迁移指南

## 概述

pfinal-asyncio-gamekit v3.0 是一个重大版本升级，引入了许多新特性和架构改进。本指南将帮助您从 v2.x 顺利迁移到 v3.0。

## 🎯 主要变更

### 1. 架构重构

#### Room 类模块化

**v2.x:**
```php
use PfinalClub\AsyncioGamekit\Room;

class MyRoom extends Room {
    // 所有功能在一个大文件中
}
```

**v3.0:**
```php
// Room 已拆分为多个 Trait
use PfinalClub\AsyncioGamekit\Room\Room;
use PfinalClub\AsyncioGamekit\Room\Traits\{
    PlayerManagement,
    LifecycleManagement,
    TimerManagement,
    DataStorage
};

class MyRoom extends Room {
    // 功能通过 Trait 组合
}
```

**迁移步骤：**
- 更新命名空间引用
- 如果您扩展了 Room 类，检查是否使用了内部方法
- 兼容层：旧的 `PfinalClub\AsyncioGamekit\Room` 仍然可用（继承自新的 `Room\Room`）

### 2. 依赖注入容器

**v3.0 新增:**
```php
use PfinalClub\AsyncioGamekit\Container\SimpleContainer;

$container = new SimpleContainer();

// 注册服务
$container->singleton('logger', function($c) {
    return new Logger();
});

// 使用服务
$logger = $container->get('logger');
```

**建议：**
- 使用依赖注入替代硬编码依赖
- 提高代码可测试性和可维护性

### 3. 限流器

**v3.0 新增:**
```php
use PfinalClub\AsyncioGamekit\RateLimit\TokenBucketLimiter;

$limiter = new TokenBucketLimiter();

// 检查是否允许（玩家ID，容量20，速率20/秒）
if ($limiter->allow($playerId, 20, 20)) {
    // 处理请求
} else {
    // 拒绝请求
}
```

**GameServer 自动集成：**
- 默认每个玩家每秒最多 20 条消息
- 可在构造函数配置中自定义

### 4. 内存管理

**v3.0 新增:**
```php
use PfinalClub\AsyncioGamekit\Memory\MemoryManager;

$memoryManager = new MemoryManager(256, 0.8); // 256MB限制，80%告警

// 检查内存
if ($memoryManager->isOverLimit()) {
    $memoryManager->gc(); // 垃圾回收
}

// 获取统计信息
$stats = $memoryManager->getStats();
```

**RoomManager 自动集成：**
- 默认 256MB 内存限制
- 自动清理和垃圾回收

### 5. 中间件系统

**v3.0 新增:**
```php
use PfinalClub\AsyncioGamekit\Middleware\{
    MiddlewarePipeline,
    LoggingMiddleware,
    ValidationMiddleware
};

$pipeline = new MiddlewarePipeline();

// 添加中间件
$pipeline->add(new LoggingMiddleware());
$pipeline->add(new ValidationMiddleware());

// 处理消息
$pipeline->process($player, $event, $data, function($player, $event, $data) {
    // 最终处理器
});
```

**内置中间件：**
- `LoggingMiddleware` - 日志记录
- `ValidationMiddleware` - 数据验证
- `PerformanceMiddleware` - 性能监控

### 6. 事件总线

**v3.0 新增:**
```php
use PfinalClub\AsyncioGamekit\Event\{EventBus, Event};

$eventBus = new EventBus();

// 订阅事件
$eventBus->subscribe('player.join', function($event) {
    $data = $event->getData();
    // 处理事件
});

// 发布事件
$eventBus->publish('player.join', ['player_id' => '123']);

// 异步发布
$eventBus->publishAsync('player.join', ['player_id' => '123']);
```

**使用场景：**
- 跨房间通信
- 解耦模块
- 事件驱动架构

### 7. 权限系统（RBAC）

**v3.0 新增:**
```php
use PfinalClub\AsyncioGamekit\Permission\{
    PermissionManager,
    Role,
    Permission
};

$permissionManager = new PermissionManager();

// 分配角色
$permissionManager->assignRole($player, Role::MODERATOR);

// 检查权限
if ($permissionManager->hasPermission($player, Permission::ROOM_KICK)) {
    // 执行操作
}

// 要求权限（不满足抛出异常）
$permissionManager->requirePermission($player, Permission::ROOM_START);
```

**预定义角色：**
- `PLAYER` - 普通玩家
- `MODERATOR` - 版主
- `ADMIN` - 管理员
- `OWNER` - 房主

### 8. 批量广播优化

**v2.x:**
```php
// 每次 broadcast 都单独发送
$room->broadcast('event', $data);
```

**v3.0 优化:**
```php
// 消息自动预编码，减少重复 JSON 编码
$room->broadcast('event', $data);

// 使用批量广播队列（可选）
use PfinalClub\AsyncioGamekit\Broadcast\BroadcastQueue;

$queue = new BroadcastQueue(0.05, 100); // 50ms刷新，100条批处理
$queue->enqueue($player, $message);
$queue->flush(); // 批量发送
```

### 9. 审计日志

**v3.0 新增:**
```php
use PfinalClub\AsyncioGamekit\Audit\AuditLogger;

$auditLogger = new AuditLogger();

// 记录操作
$auditLogger->log('player.kicked', [
    'user_id' => $player->getId(),
    'target_id' => $targetPlayer->getId(),
    'room_id' => $room->getId(),
]);

// 查询日志
$logs = $auditLogger->getLogs(100);
$userLogs = $auditLogger->getLogsByUser($userId);
```

## 🔧 配置变更

### RoomManager

**v2.x:**
```php
$roomManager = new RoomManager();
```

**v3.0:**
```php
use PfinalClub\AsyncioGamekit\Memory\MemoryManager;

$roomManager = new RoomManager(
    new MemoryManager(256, 0.8), // 内存管理器
    1000 // 最大房间数
);

// 定期维护
$roomManager->periodicMaintenance();
```

### GameServer

**v2.x:**
```php
$server = new GameServer('0.0.0.0', 2345, [
    'count' => 4,
]);
```

**v3.0:**
```php
use PfinalClub\AsyncioGamekit\RateLimit\TokenBucketLimiter;

$server = new GameServer('0.0.0.0', 2345, [
    'count' => 4,
    'rate_limiter' => new TokenBucketLimiter(),
    'allowed_room_classes' => [MyRoom::class], // 白名单
]);
```

## ⚠️ 破坏性变更

### 1. Room 命名空间变更

- **旧:** `PfinalClub\AsyncioGamekit\Room`
- **新:** `PfinalClub\AsyncioGamekit\Room\Room`
- **兼容层:** 旧命名空间仍可用，但已标记为 `@deprecated`

### 2. RoomManager 构造函数

- v2.x: 无参数构造函数
- v3.0: 可选参数 `MemoryManager` 和 `maxRooms`
- **兼容性:** 向后兼容（参数可选）

### 3. Room::broadcast() 行为

- v3.0: 自动移除发送失败的玩家（连接已断开）
- v2.x: 不自动移除
- **影响:** 测试代码可能需要调整（使用真实连接）

### 4. Room::removePlayer() 延迟销毁

- v3.0: 空房间延迟 5 秒销毁（给新玩家加入机会）
- v2.x: 立即销毁
- **影响:** 房间生命周期管理逻辑可能需要调整

## 📋 迁移清单

### 必须执行

- [ ] 更新 composer 依赖到 v3.0
- [ ] 检查 Room 类继承（更新命名空间）
- [ ] 运行完整测试套件
- [ ] 更新 PHP 版本要求（PHP 8.1+）

### 建议执行

- [ ] 集成依赖注入容器
- [ ] 添加限流保护
- [ ] 启用内存监控
- [ ] 使用中间件系统
- [ ] 实现权限控制
- [ ] 添加审计日志

### 可选执行

- [ ] 集成事件总线
- [ ] 使用批量广播队列
- [ ] 添加性能监控
- [ ] 实现自定义中间件

## 🚀 升级步骤

### 1. 更新依赖

```bash
composer require pfinal-club/asyncio-gamekit:^3.0
```

### 2. 更新代码

```php
// 搜索并替换（如果直接使用新命名空间）
// 旧: use PfinalClub\AsyncioGamekit\Room;
// 新: use PfinalClub\AsyncioGamekit\Room\Room;
```

### 3. 运行测试

```bash
./vendor/bin/phpunit
```

### 4. 逐步集成新特性

从最有价值的特性开始：
1. 限流器（安全）
2. 内存管理（稳定性）
3. 日志中间件（可观测性）
4. 权限系统（安全）

## 🐛 常见问题

### Q: 升级后测试失败，提示玩家被移除

**A:** v3.0 的 `Room::broadcast()` 会自动移除发送失败的玩家。在测试中，确保 Player 对象有有效的连接对象，或使用 mock 对象。

```php
// 测试中使用 mock
$connection = $this->createMock(TcpConnection::class);
$connection->method('send')->willReturn(true);
$player = new Player('id', 'name', $connection);
```

### Q: RoomManager 构造函数参数变化

**A:** v3.0 的 RoomManager 支持可选参数，向后兼容。但建议显式传递参数：

```php
// 推荐
$roomManager = new RoomManager(
    new MemoryManager(256, 0.8),
    1000
);

// 兼容（使用默认值）
$roomManager = new RoomManager();
```

### Q: 性能有提升吗？

**A:** 是的！v3.0 包含多项性能优化：
- JSON 预编码（减少重复编码）
- 房间索引（快速查找）
- 配置缓存（减少数组访问）
- 批量广播（减少网络 IO）

## 📚 更多资源

- [完整文档](../README.md)
- [API 文档](./API.md)
- [最佳实践](./BEST_PRACTICES.md)
- [性能优化指南](./OPTIMIZATION_GUIDE.md)
- [变更日志](../CHANGELOG.md)

## 🆘 获取帮助

如果遇到迁移问题：
1. 查看 [GitHub Issues](https://github.com/pfinal-club/asyncio-gamekit/issues)
2. 提交新 Issue（附带详细信息）
3. 查看示例项目
4. 联系维护者

## 📝 反馈

我们重视您的反馈！请通过 GitHub Issues 或 Pull Requests 分享您的迁移体验和建议。

