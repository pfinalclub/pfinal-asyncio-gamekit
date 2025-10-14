# 测试指南

本文档介绍如何在 pfinal-asyncio-gamekit 中编写和运行测试。

---

## 🧪 测试框架

项目使用 **PHPUnit 10** 作为测试框架。

### 安装测试依赖

```bash
composer install --dev
```

---

## 🚀 运行测试

### 运行所有测试

```bash
composer test
# 或
./vendor/bin/phpunit
```

### 运行特定测试文件

```bash
./vendor/bin/phpunit tests/RoomTest.php
```

### 运行特定测试方法

```bash
./vendor/bin/phpunit --filter testAddPlayer
```

### 生成覆盖率报告

```bash
composer test-coverage
# 报告生成在 coverage/ 目录
```

---

## 📁 测试结构

```
tests/
├── Helpers/              # 测试辅助类
│   ├── TestRoom.php     # 测试用的空房间实现
│   └── MockConnection.php # 模拟连接对象
├── PlayerTest.php       # Player 单元测试
├── RoomTest.php         # Room 单元测试
└── RoomManagerTest.php  # RoomManager 单元测试
```

---

## ✍️ 编写测试

### 基本测试结构

```php
<?php

namespace PfinalClub\AsyncioGamekit\Tests;

use PHPUnit\Framework\TestCase;
use PfinalClub\AsyncioGamekit\Player;

class PlayerTest extends TestCase
{
    public function testPlayerCanBecreated(): void
    {
        // Arrange (准备)
        $id = 'player_001';
        $name = 'Alice';
        
        // Act (执行)
        $player = new Player($id, null, $name);
        
        // Assert (断言)
        $this->assertEquals($id, $player->getId());
        $this->assertEquals($name, $player->getName());
    }
}
```

### 使用测试辅助类

```php
use PfinalClub\AsyncioGamekit\Tests\Helpers\TestRoom;
use PfinalClub\AsyncioGamekit\Tests\Helpers\MockConnection;

public function testRoomBroadcast(): void
{
    // 创建测试房间
    $room = new TestRoom('room_001');
    
    // 创建模拟连接
    $connection = new MockConnection();
    $player = new Player('p1', $connection, 'Alice');
    
    // 添加玩家
    $room->addPlayer($player);
    
    // 广播消息
    $room->broadcast('test:event', ['data' => 'hello']);
    
    // 验证消息
    $message = $connection->getLastMessage();
    $this->assertEquals('test:event', $message['event']);
    $this->assertEquals('hello', $message['data']['data']);
}
```

### 测试异常

```php
use PfinalClub\AsyncioGamekit\Exceptions\RoomException;

public function testAddPlayerToFullRoom(): void
{
    $room = new TestRoom('room_001', ['max_players' => 2]);
    $player1 = new Player('p1', null, 'Player1');
    $player2 = new Player('p2', null, 'Player2');
    $player3 = new Player('p3', null, 'Player3');
    
    $room->addPlayer($player1);
    $room->addPlayer($player2);
    
    // 期望抛出异常
    $this->expectException(RoomException::class);
    $this->expectExceptionMessage('Room room_001 is full');
    
    $room->addPlayer($player3);
}
```

---

## 📝 最佳实践

### 1. 测试命名

使用清晰的测试方法名：

```php
// ✅ 好的命名
public function testPlayerCanJoinRoom(): void
public function testRoomBroadcastsToAllPlayers(): void
public function testAddPlayerToFullRoomThrowsException(): void

// ❌ 不好的命名
public function test1(): void
public function testRoom(): void
```

### 2. AAA 模式

遵循 **Arrange-Act-Assert** 模式：

```php
public function testExample(): void
{
    // Arrange: 准备测试数据
    $room = new TestRoom('room_001');
    $player = new Player('p1', null, 'Alice');
    
    // Act: 执行被测试的操作
    $result = $room->addPlayer($player);
    
    // Assert: 验证结果
    $this->assertTrue($result);
    $this->assertEquals(1, $room->getPlayerCount());
}
```

### 3. 独立性

每个测试应该是独立的：

```php
class RoomTest extends TestCase
{
    private TestRoom $room;
    
    // 每个测试前重新创建
    protected function setUp(): void
    {
        $this->room = new TestRoom('room_001');
    }
    
    // 每个测试后清理
    protected function tearDown(): void
    {
        $this->room = null;
    }
}
```

### 4. 边界测试

测试边界条件：

```php
public function testRoomWithZeroPlayers(): void
{
    $room = new TestRoom('room_001');
    $this->assertEquals(0, $room->getPlayerCount());
}

public function testRoomWithMaxPlayers(): void
{
    $room = new TestRoom('room_001', ['max_players' => 2]);
    $player1 = new Player('p1', null, 'Player1');
    $player2 = new Player('p2', null, 'Player2');
    
    $room->addPlayer($player1);
    $room->addPlayer($player2);
    
    $this->assertEquals(2, $room->getPlayerCount());
}
```

---

## 🎯 测试覆盖率

### 查看覆盖率

```bash
composer test-coverage
```

### 覆盖率报告

报告生成在 `coverage/index.html`，用浏览器打开：

```bash
open coverage/index.html
```

### 目标

- **整体覆盖率**: >= 80%
- **核心类覆盖率**: >= 90%

---

## 🔧 Mock 和 Stub

### 使用 MockConnection

```php
$connection = new MockConnection();
$player = new Player('p1', $connection, 'Alice');

$player->send('test:event', ['message' => 'Hello']);

// 验证发送的消息
$lastMessage = $connection->getLastMessage();
$this->assertEquals('test:event', $lastMessage['event']);
```

### 使用 PHPUnit Mock

```php
// Mock Player
$player = $this->createMock(Player::class);
$player->method('getId')->willReturn('p1');
$player->method('getName')->willReturn('Alice');

// Stub 方法
$player->expects($this->once())
    ->method('send')
    ->with('test:event', ['data' => 'hello']);
```

---

## 🐛 调试测试

### 输出调试信息

```php
public function testDebug(): void
{
    $room = new TestRoom('room_001');
    
    // 输出调试信息
    var_dump($room->toArray());
    
    $this->assertTrue(true);
}
```

### 使用 --debug 选项

```bash
./vendor/bin/phpunit --debug
```

### 使用 --verbose 选项

```bash
./vendor/bin/phpunit --verbose
```

---

## 📊 持续集成

### GitHub Actions

创建 `.github/workflows/tests.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.3'
        extensions: pcntl, posix
        
    - name: Install dependencies
      run: composer install --prefer-dist
      
    - name: Run tests
      run: composer test
      
    - name: Generate coverage
      run: composer test-coverage
      
    - name: Upload coverage
      uses: codecov/codecov-action@v2
```

---

## 📋 测试清单

在提交代码前：

- [ ] 所有测试通过
- [ ] 新功能有对应的测试
- [ ] 测试覆盖率达标
- [ ] 测试命名清晰
- [ ] 没有跳过的测试（除非有充分理由）
- [ ] 没有调试输出

---

## 🆘 常见问题

### Q: 测试运行很慢怎么办？

```bash
# 并行运行测试
./vendor/bin/phpunit --process-isolation
```

### Q: 如何跳过某个测试？

```php
public function testSomething(): void
{
    $this->markTestSkipped('暂时跳过，等待修复');
}
```

### Q: 如何测试私有方法？

使用反射：

```php
private function callPrivateMethod($object, $methodName, ...$args)
{
    $reflection = new \ReflectionClass($object);
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);
    return $method->invoke($object, ...$args);
}
```

---

**更新日期：** 2024-10-14  
**版本：** 1.0.0

