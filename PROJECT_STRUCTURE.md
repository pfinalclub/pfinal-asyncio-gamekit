# 项目结构说明

## 📁 目录结构

```
pfinal-asyncio-gamekit/
│
├── src/                          # 核心源代码
│   ├── Room.php                 # Room 基类
│   ├── Player.php               # Player 类
│   ├── RoomManager.php          # 房间管理器
│   ├── GameServer.php           # WebSocket 游戏服务器
│   ├── Exceptions/             # 异常处理
│   │   ├── GameException.php
│   │   ├── RoomException.php
│   │   ├── PlayerException.php
│   │   └── ServerException.php
│   ├── Logger/                  # 日志系统
│   │   ├── Logger.php
│   │   ├── LoggerFactory.php
│   │   ├── LogLevel.php
│   │   ├── LogHandlerInterface.php
│   │   ├── ConsoleLogHandler.php
│   │   ├── FileLogHandler.php
│   │   └── WorkermanLogHandler.php
│   ├── Persistence/             # 状态持久化
│   │   ├── PersistenceAdapterInterface.php
│   │   ├── FileAdapter.php
│   │   ├── RedisAdapter.php
│   │   ├── MemoryAdapter.php
│   │   └── RoomStateManager.php
│   └── LoadBalance/             # 负载均衡
│       ├── LoadBalancerInterface.php
│       ├── RoundRobinBalancer.php
│       ├── LeastConnectionsBalancer.php
│       ├── WeightedBalancer.php
│       └── RoomDistributor.php
│
├── examples/                     # 示例代码
│   ├── SimpleGame.php           # 简单倒计时游戏
│   ├── CardGame.php             # 卡牌游戏示例
│   ├── WebSocketServer.php      # WebSocket 服务器示例
│   ├── AdvancedGame.php         # 高级特性示例
│   ├── WorkermanAdvancedGame.php # Workerman 高级示例
│   └── client.html              # 网页客户端示例
│
├── tests/                        # 单元测试
│   ├── PlayerTest.php
│   ├── RoomTest.php
│   ├── RoomManagerTest.php
│   └── Helpers/
│       ├── TestRoom.php
│       └── MockConnection.php
│
├── docs/                         # 文档目录
│   ├── GUIDE.md                 # 开发指南
│   ├── API.md                   # API 参考文档
│   ├── IMPROVEMENTS.md          # 改进说明
│   ├── MIGRATION_V2.md          # v2.0 迁移指南
│   ├── PRODUCTION_GUIDE.md      # 生产部署指南
│   └── TESTING.md               # 测试指南
│
├── config/                       # 配置文件
│   ├── logger.php               # 开发环境日志配置
│   ├── logger.production.php    # 生产环境日志配置
│   └── server.php               # 服务器配置
│
├── logs/                         # 日志目录
├── storage/                      # 存储目录
├── runtime/                      # 运行时目录
│
├── composer.json                 # Composer 配置
├── phpunit.xml                   # PHPUnit 配置
├── README.md                     # 项目说明
├── CHANGELOG.md                  # 更新日志
├── CONTRIBUTING.md               # 贡献指南
├── INSTALL.md                    # 安装说明
├── PROJECT_STRUCTURE.md          # 项目结构说明（本文件）
├── LICENSE                       # MIT 许可证
└── VERSION                       # 版本号文件
```

## 🎯 核心文件说明

### src/Room.php

**Room 基类** - 游戏房间的核心抽象类

**功能：**
- ✅ 完整的生命周期钩子（onCreate, onStart, run, onDestroy）
- ✅ 玩家管理（加入、离开、获取）
- ✅ 消息广播（同步和异步）
- ✅ 定时器管理
- ✅ 房间状态管理
- ✅ 自定义数据存储

**生命周期：**
```
constructor → onCreate() → onStart() → run() → onDestroy()
```

### src/Player.php

**Player 类** - 玩家通信和状态封装

**功能：**
- ✅ 消息发送
- ✅ 自定义数据存储
- ✅ 准备状态管理
- ✅ 房间关联

### src/RoomManager.php

**RoomManager 类** - 多房间管理器

**功能：**
- ✅ 创建/删除房间
- ✅ 玩家加入/离开房间
- ✅ 快速匹配
- ✅ 房间统计

### src/GameServer.php

**GameServer 类** - WebSocket 游戏服务器

**功能：**
- ✅ WebSocket 服务器
- ✅ 自动处理系统事件
- ✅ 房间管理集成
- ✅ 连接管理

## 📚 示例说明

### examples/SimpleGame.php

**简单倒计时游戏**

- 展示基本的 Room 使用
- 演示生命周期钩子
- 简单的游戏循环

**运行：**
```bash
php examples/SimpleGame.php
```

### examples/CardGame.php

**卡牌游戏**

- 更复杂的游戏逻辑
- 回合制游戏示例
- 定时器使用
- 玩家交互处理

**运行：**
```bash
php examples/CardGame.php
```

### examples/WebSocketServer.php

**WebSocket 游戏服务器**

- 完整的服务器实现
- 猜数字游戏
- 实时通信
- 超时处理

**运行：**
```bash
php examples/WebSocketServer.php
```

然后在浏览器打开 `examples/client.html`

### examples/client.html

**网页客户端**

- 漂亮的 UI 界面
- WebSocket 连接
- 实时消息显示
- 游戏交互

### examples/AdvancedGame.php

**高级特性示例**

展示新特性的使用：
- 日志系统
- 异常处理
- 状态持久化
- 负载均衡

**运行：**
```bash
php examples/AdvancedGame.php
```

### examples/WorkermanAdvancedGame.php

**Workerman 高级示例**

展示在 Workerman 环境中使用高级特性。

**运行：**
```bash
php examples/WorkermanAdvancedGame.php
```

## 📖 文档说明

### docs/GUIDE.md

**开发指南**

包含：
- 核心概念
- 设计模式
- 最佳实践
- 常见问题
- 性能优化
- 调试技巧

### docs/API.md

**API 参考文档**

详细的 API 说明：
- Room 类完整 API
- Player 类完整 API
- RoomManager 类完整 API
- GameServer 类完整 API

### docs/IMPROVEMENTS.md

**改进说明文档**

详细说明新增的高级特性：
- 单元测试框架
- 异常处理系统
- 日志系统
- 状态持久化
- 负载均衡

### docs/MIGRATION_V2.md

**v2.0 迁移指南**

从 v1.x (Generator) 迁移到 v2.0 (Fiber) 的详细指南。

### docs/PRODUCTION_GUIDE.md

**生产部署指南**

生产环境部署的最佳实践和配置建议。

### docs/TESTING.md

**测试指南**

单元测试的使用方法和最佳实践。

## 🚀 快速开始

### 1. 安装依赖

```bash
composer install
```

### 2. 运行示例

直接运行示例文件：
```bash
php examples/SimpleGame.php
```

## 🎮 创建你的第一个游戏

```php
<?php
use PfinalClub\AsyncioGamekit\Room;
use function PfinalClub\Asyncio\{run, sleep};

class MyGame extends Room
{
    protected function run(): mixed
    {
        // 你的游戏逻辑
        $this->broadcast('game:start', []);
        sleep(10);
        $this->destroy();
    }
}

function main(): mixed
{
    $room = new MyGame('room_001');
    $room->start();
}

run(main(...));
```

## 🔧 核心依赖

- **pfinalclub/asyncio** (^2.1) - 异步 IO 框架（基于 PHP Fiber，性能拉满）
- **workerman/workerman** (^4.1) - PHP 异步框架
- **workerman/gateway-worker** (^3.0) - Gateway Worker
- **workerman/channel** (^1.1) - 进程间通信
- **phpunit/phpunit** (^10.0) - 单元测试框架（开发依赖）

## 🧪 测试

项目包含完整的单元测试：

```bash
# 运行所有测试
composer test

# 生成覆盖率报告
composer test-coverage
```

测试覆盖：
- Player 类：数据管理、准备状态、序列化
- Room 类：玩家管理、生命周期、配置
- RoomManager 类：房间创建、加入/离开、快速匹配

## 📝 许可证

MIT License - 查看 [LICENSE](LICENSE) 文件

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 📮 联系方式

- GitHub: https://github.com/pfinalclub
- Email: pfinal@126.com

---

**开始你的异步游戏开发之旅吧！** 🎮🚀


