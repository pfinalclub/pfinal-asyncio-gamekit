# 更新日志

本项目的所有重要更改都会记录在此文件中。

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
