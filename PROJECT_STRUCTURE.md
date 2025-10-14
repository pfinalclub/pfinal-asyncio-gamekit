# 项目结构说明

## 📁 目录结构

```
pfinal-asyncio-gamekit/
│
├── src/                          # 核心源代码
│   ├── Room.php                 # Room 基类
│   ├── Player.php               # Player 类
│   ├── RoomManager.php          # 房间管理器
│   └── GameServer.php           # WebSocket 游戏服务器
│
├── examples/                     # 示例代码
│   ├── SimpleGame.php           # 简单倒计时游戏
│   ├── CardGame.php             # 卡牌游戏示例
│   ├── WebSocketServer.php      # WebSocket 服务器示例
│   └── client.html              # 网页客户端示例
│
├── docs/                         # 文档目录
│   ├── GUIDE.md                 # 开发指南
│   └── API.md                   # API 参考文档
│
├── composer.json                 # Composer 配置
├── README.md                     # 项目说明
├── CHANGELOG.md                  # 更新日志
├── LICENSE                       # MIT 许可证
├── .gitignore                    # Git 忽略文件
├── start.sh                      # Linux/Mac 启动脚本
└── start.bat                     # Windows 启动脚本
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

## 🚀 快速开始

### 1. 安装依赖

```bash
composer install
```

### 2. 运行示例

**Linux/Mac:**
```bash
./start.sh
```

**Windows:**
```bash
start.bat
```

或者直接运行：
```bash
php examples/SimpleGame.php
```

## 🎮 创建你的第一个游戏

```php
<?php
use PfinalClub\AsyncioGamekit\Room;
use Generator;

class MyGame extends Room
{
    protected function run(): Generator
    {
        // 你的游戏逻辑
        $this->broadcast('game:start', []);
        yield sleep(10);
        yield from $this->destroy();
    }
}
```

## 🔧 核心依赖

- **pfinalclub/asyncio** - 异步 IO 框架
- **workerman/workerman** - PHP 异步框架

## 📝 许可证

MIT License - 查看 [LICENSE](LICENSE) 文件

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 📮 联系方式

- GitHub: https://github.com/pfinalclub
- Email: pfinal@126.com

---

**开始你的异步游戏开发之旅吧！** 🎮🚀

