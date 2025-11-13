# 更新日志

本项目的所有重要更改都会记录在此文件中。

## [2.0.1] - 2025-01-XX

### 升级 ⬆️

- **pfinalclub/asyncio**: ^2.0 → ^2.1
- 性能直接拉满，充分利用 asyncio 2.1 的性能优化

### 修复 🐛

- 修复 `examples/WebSocketServer.php` 中的 Generator 语法，更新为 Fiber 语法以兼容 asyncio 2.1
  - `onStart()`: `Generator` → `mixed`，移除 `yield sleep(1)`
  - `run()`: `Generator` → `mixed`，移除 `yield sleep()` 和 `yield from`
  - `onPlayerMessage()`: `Generator` → `mixed`，移除 `yield`
- 修复 `Room::broadcast()` 在测试环境中误移除玩家的问题
  - 区分"没有连接"和"连接失败"两种情况
  - 只有真正的连接失败才会移除玩家
  - 兼容测试环境中的 null 连接

### 优化 ⚡

#### 高优先级优化

- **Room::broadcast()** - 连接健康检查与错误处理
  - 自动检测连接失效并移除断线玩家
  - 避免向无效连接发送消息
  - 提升广播可靠性

- **RoomManager** - 房间自动清理机制
  - 新增 `cleanupFinishedRooms()` 方法，自动清理已结束的房间
  - 可配置清理间隔（默认 5 分钟）
  - 异步销毁房间，避免阻塞主流程
  - 防止内存泄漏

#### 中优先级优化

- **Player::send()** - 完善错误处理
  - 返回 bool 表示发送是否成功
  - 捕获 JSON 编码异常
  - 捕获连接发送失败异常

- **RoomManager::findAvailableRoom()** - 性能优化
  - 使用 `instanceof` 替代 `get_class()` 判断
  - 直接调用 `getMaxPlayers()` 避免 `toArray()` 开销
  - 提升房间查找效率

- **Room::toArray()** - 性能优化
  - 添加 `$includePlayers` 参数，可选择不序列化玩家
  - 新增 `toArrayLight()` 轻量级方法，只返回基础信息
  - 减少不必要的数据序列化开销

- **Room::destroy()** - 完善清理机制
  - 添加 try-catch 保护 `onDestroy()` 调用
  - 确保定时器清理即使出错也能继续
  - 清理自定义数据，防止内存泄漏
  - 更完善的资源释放

- **Room::handleRoomError()** - 智能错误恢复
  - 区分 RoomException 和严重错误
  - RoomException 通知玩家可恢复
  - 严重错误时自动安全关闭房间
  - 延迟销毁给玩家接收错误消息的时间

### 新增 ✨

- **Room::getMaxPlayers()** - 获取最大玩家数
- **Room::getConfig()** - 获取房间配置
- **RoomManager::setCleanupInterval()** - 设置清理间隔
- **RoomManager::getCleanupInterval()** - 获取清理间隔

### 受益 🚀

- 性能进一步提升，充分利用 asyncio 2.1 的性能优化
- 更好的事件循环性能
- 更强的并发处理能力
- 所有示例代码已完全兼容 asyncio 2.1
- 更健壮的错误处理和恢复机制
- 自动内存管理，防止内存泄漏
- 更高效的房间查找和数据序列化

---

## [2.0.0] - 2025-01-XX

### 重大变更 💥

#### 升级到 pfinalclub/asyncio v2.0
- 从 Generator 协程迁移到 Fiber 协程
- 性能提升 2-3 倍
- API 简化，移除所有 yield 语法

#### 破坏性变更
- 所有异步方法签名从 `Generator` 改为 `mixed`
- 移除 `yield` 和 `yield from` 关键字
- `create_task` 需要传入 callable 而不是 Generator
- `run()` 调用方式改为 `run(callable(...))`
- PHP 最低版本要求降低到 8.1（适配 asyncio v2）

#### 迁移指南
详见：`docs/MIGRATION_V2.md`

### 升级 ⬆️
- pfinalclub/asyncio: ^1.0 → ^2.0
- PHP 版本要求：>=8.3 → >=8.1

### 受益 🚀
- 性能提升 2-3 倍
- 代码更简洁易读
- 更好的错误堆栈
- 支持 asyncio v2 的新特性（事件循环优化、多进程、并发控制等）

---

## [1.0.0] - 2024-10-14

### 新增 🎉

#### 单元测试框架
- 添加 PHPUnit 测试框架配置
- 新增 `PlayerTest.php` - Player 类完整测试
- 新增 `RoomTest.php` - Room 类完整测试
- 新增 `RoomManagerTest.php` - RoomManager 类完整测试
- 测试覆盖核心功能：玩家管理、房间生命周期、房间匹配等

#### 异常处理系统
- 新增 `Exceptions/GameException.php` - 基础异常类，支持上下文信息
- 新增 `Exceptions/RoomException.php` - 房间相关异常（房间满员、房间未找到等）
- 新增 `Exceptions/PlayerException.php` - 玩家相关异常
- 新增 `Exceptions/ServerException.php` - 服务器相关异常
- 所有异常支持结构化错误信息和上下文数据
- 在 `Room`、`RoomManager`、`GameServer` 中集成异常处理

#### 日志系统
- 新增 `Logger/LogLevel.php` - 8 个日志级别定义
- 新增 `Logger/Logger.php` - 核心日志类，支持占位符插值
- 新增 `Logger/LogHandlerInterface.php` - 日志处理器接口
- 新增 `Logger/ConsoleLogHandler.php` - 控制台彩色输出
- 新增 `Logger/FileLogHandler.php` - 文件日志，支持自动轮转
- 新增 `Logger/LoggerFactory.php` - 日志工厂，简化使用
- 在核心类中集成日志记录

#### 状态持久化
- 新增 `Persistence/PersistenceAdapterInterface.php` - 持久化接口
- 新增 `Persistence/FileAdapter.php` - 文件存储适配器
- 新增 `Persistence/RedisAdapter.php` - Redis 存储适配器
- 新增 `Persistence/MemoryAdapter.php` - 内存存储（测试用）
- 新增 `Persistence/RoomStateManager.php` - 房间状态管理器
- 支持房间状态保存/恢复、玩家会话、游戏统计

#### 负载均衡
- 新增 `LoadBalance/LoadBalancerInterface.php` - 负载均衡器接口
- 新增 `LoadBalance/RoundRobinBalancer.php` - 轮询策略
- 新增 `LoadBalance/LeastConnectionsBalancer.php` - 最少连接策略
- 新增 `LoadBalance/WeightedBalancer.php` - 加权策略
- 新增 `LoadBalance/RoomDistributor.php` - 房间分配器
- 支持多进程房间分配和状态跟踪

#### 文档和示例
- 新增 `docs/IMPROVEMENTS.md` - 详细的改进说明文档
- 新增 `examples/AdvancedGame.php` - 展示所有新特性的高级示例
- 更新 `README.md` - 添加新特性使用说明
- 新增 `CHANGELOG.md` - 更新日志

### 改进 ⚡

#### Room 类
- 添加异常抛出机制，提供更详细的错误信息
- 集成日志记录，自动记录玩家加入/离开等事件
- `onPlayerJoin` 和 `onPlayerLeave` 现在默认记录日志
- 新增 `handleRoomError` 方法处理运行时错误

#### GameServer 类
- 改进错误处理，区分框架异常和系统异常
- 添加结构化错误响应，包含错误代码和上下文
- 集成日志记录玩家连接/断开事件
- 新增 `logError` 方法统一错误记录

#### RoomManager 类
- 添加异常处理，`joinRoom` 失败时抛出明确异常
- 改进房间查找逻辑

### 依赖 📦

- 添加开发依赖 `phpunit/phpunit: ^10.0`
- 保持对 `php: >=8.3` 的要求
- 保持对 `pfinalclub/asyncio: ^1.0` 的依赖
- 保持对 `workerman/workerman: ^4.1` 的依赖

### 最佳实践 💡

- 建议生产环境配置日志系统
- 推荐使用 Redis 适配器进行状态持久化
- 建议根据服务器负载选择合适的负载均衡策略
- 推荐为自定义游戏房间添加单元测试

---

## [1.0.0] - 初始版本

### 新增
- Room 基类，支持异步游戏逻辑
- Player 通信封装
- RoomManager 房间管理器
- GameServer WebSocket 游戏服务器
- 示例游戏：SimpleGame、CardGame、WebSocketServer
- 基础文档和使用指南

---

格式遵循 [Keep a Changelog](https://keepachangelog.com/zh-CN/1.0.0/)，
版本号遵循 [语义化版本](https://semver.org/lang/zh-CN/)。
