# pfinal-asyncio-gamekit v1.0.0 发布说明

## 🎉 首次正式发布

我们很高兴地宣布 **pfinal-asyncio-gamekit v1.0.0** 正式发布！

这是一个基于 Workerman 和 pfinal-asyncio 的轻量级异步游戏逻辑框架，专为小型多人在线游戏设计。

---

## ✨ 核心特性

### 基础功能
- 🎮 **Room 基类** - 完整的异步房间生命周期管理
- 👥 **Player 通信** - 简单易用的玩家消息收发
- 📢 **异步广播** - 支持即时和延迟广播机制
- ⏰ **定时器系统** - 灵活的定时任务管理
- 🎯 **RoomManager** - 多房间管理和快速匹配
- 🌐 **GameServer** - 开箱即用的 WebSocket 游戏服务器

### 高级功能
- 🧪 **单元测试** - 完整的 PHPUnit 测试框架（39个测试用例）
- 🚨 **异常处理** - 结构化异常系统，带上下文信息
- 📝 **日志系统** - 8级日志，支持控制台彩色输出和文件轮转
- 💾 **状态持久化** - 支持 Redis、文件等多种存储方式
- ⚖️ **负载均衡** - 多进程房间分配，支持轮询、最少连接等策略

---

## 📦 安装

```bash
composer require pfinalclub/asyncio-gamekit
```

### 系统要求

- PHP >= 8.3
- pfinalclub/asyncio >= 1.0
- workerman/workerman >= 4.1

---

## 🚀 快速开始

### 1. 创建游戏房间

```php
use PfinalClub\AsyncioGamekit\Room;
use PfinalClub\AsyncioGamekit\Player;
use function PfinalClub\Asyncio\{run, sleep};

class MyGameRoom extends Room
{
    protected function run(): Generator
    {
        $this->broadcast('game:start', ['message' => '游戏开始！']);
        
        for ($round = 1; $round <= 3; $round++) {
            $this->broadcast('game:round', ['round' => $round]);
            yield sleep(5);
        }
        
        $this->broadcast('game:end', ['message' => '游戏结束！']);
        yield from $this->destroy();
    }
}
```

### 2. 启动 WebSocket 服务器

```php
use PfinalClub\AsyncioGamekit\GameServer;

$server = new GameServer('0.0.0.0', 2345, [
    'name' => 'MyGameServer',
    'count' => 4,
]);

$server->run();
```

---

## 📚 文档

- [README.md](README.md) - 完整的使用说明
- [docs/API.md](docs/API.md) - API 参考文档
- [docs/GUIDE.md](docs/GUIDE.md) - 开发指南
- [docs/IMPROVEMENTS.md](docs/IMPROVEMENTS.md) - 高级特性说明
- [CHANGELOG.md](CHANGELOG.md) - 更新日志

---

## 🎮 示例

项目包含多个完整示例：

- **examples/SimpleGame.php** - 简单的倒计时游戏
- **examples/CardGame.php** - 回合制卡牌游戏
- **examples/WebSocketServer.php** - WebSocket 猜数字游戏
- **examples/WorkermanAdvancedGame.php** - 展示高级特性
- **examples/client.html** - 网页游戏客户端

```bash
# 运行示例
php examples/SimpleGame.php
php examples/WebSocketServer.php
```

---

## 🧪 运行测试

```bash
# 安装开发依赖
composer install --dev

# 运行测试
composer test

# 生成覆盖率报告
composer test-coverage
```

---

## 🏗️ 架构设计

```
┌─────────────────────────────────┐
│  pfinal-asyncio-gamekit         │
│  游戏逻辑层                      │
├─────────────────────────────────┤
│  pfinal-asyncio                 │
│  协程与异步编排                  │
├─────────────────────────────────┤
│  Workerman                      │
│  WebSocket/进程管理/事件循环     │
└─────────────────────────────────┘
```

---

## 🎯 适用场景

- 🃏 卡牌游戏（斗地主、德州扑克）
- 🎲 棋牌游戏（象棋、五子棋）
- 🎯 答题游戏
- 🏁 实时对战游戏
- 🎮 回合制游戏

---

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

- GitHub: https://github.com/pfinalclub/pfinal-asyncio-gamekit
- 问题反馈: https://github.com/pfinalclub/pfinal-asyncio-gamekit/issues

---

## 📄 许可证

MIT License - 详见 [LICENSE](LICENSE) 文件

---

## 🔗 相关链接

- [pfinal-asyncio](https://github.com/pfinalclub/pfinal-asyncio) - 底层异步框架
- [Workerman](https://www.workerman.net/) - PHP 异步框架
- [Python asyncio](https://docs.python.org/3/library/asyncio.html) - 设计参考

---

## 💡 致谢

感谢所有贡献者和使用者的支持！

特别感谢：
- Workerman 团队提供的优秀异步框架
- pfinal-asyncio 提供的协程支持
- 所有测试和反馈的用户

---

**发布日期：** 2024-10-14  
**版本：** 1.0.0  
**维护者：** PFinal Club

---

Made with ❤️ by PFinal Club

