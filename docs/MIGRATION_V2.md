# pfinal-asyncio-gamekit v2.0 迁移指南

## 概述

pfinal-asyncio-gamekit v2.0 是一个重大版本更新，从 pfinalclub/asyncio v1.x (Generator) 升级到 v2.x (Fiber)。这个升级带来了显著的性能提升和更简洁的 API，但需要重写所有异步相关代码。

## 主要变更

### 1. 协程实现变更

| v1.x (Generator) | v2.0 (Fiber) |
|------------------|--------------|
| 基于 PHP Generator | 基于 PHP Fiber |
| 性能基准 | 性能提升 2-3 倍 |
| 需要 yield 关键字 | 直接调用函数 |

### 2. API 变更对比

| v1.x | v2.0 |
|------|------|
| `function f(): \Generator` | `function f(): mixed` |
| `yield sleep(1)` | `sleep(1)` |
| `yield from $this->onCreate()` | `$this->onCreate()` |
| `$results = yield gather(...)` | `$results = gather(...)` |
| `run(main())` | `run(main(...))` |
| `create_task($generator)` | `create_task(fn() => $callable)` |

### 3. 系统要求变更

| 要求 | v1.x | v2.0 |
|------|------|------|
| PHP 版本 | >= 8.3 | >= 8.1 |
| pfinalclub/asyncio | ^1.0 | ^2.0 |

## 逐步迁移指南

### 第一步：更新依赖

**composer.json**
```json
{
    "require": {
        "php": ">=8.1",
        "pfinalclub/asyncio": "^2.0",
        "workerman/workerman": "^4.1"
    }
}
```

### 第二步：更新核心类

#### Room.php 迁移

**v1.x 代码：**
```php
use Generator;

abstract class Room
{
    public function start(): Generator
    {
        yield from $this->onCreate();
        yield from $this->onStart();
        yield from $this->run();
    }
    
    abstract protected function run(): Generator;
    
    protected function onCreate(): Generator
    {
        yield;
    }
    
    protected function onStart(): Generator
    {
        yield sleep(1);
    }
    
    protected function onDestroy(): Generator
    {
        yield;
    }
    
    public function onPlayerMessage(Player $player, string $event, mixed $data): Generator
    {
        yield;
    }
}
```

**v2.0 代码：**
```php
abstract class Room
{
    public function start(): mixed
    {
        $this->onCreate();
        $this->onStart();
        $this->run();
    }
    
    abstract protected function run(): mixed;
    
    protected function onCreate(): mixed
    {
        // 空方法
    }
    
    protected function onStart(): mixed
    {
        sleep(1);
    }
    
    protected function onDestroy(): mixed
    {
        // 空方法
    }
    
    public function onPlayerMessage(Player $player, string $event, mixed $data): mixed
    {
        // 空方法
    }
}
```

#### RoomManager.php 迁移

**v1.x 代码：**
```php
public function removeRoom(string $roomId): Generator
{
    yield from $room->destroy();
}
```

**v2.0 代码：**
```php
public function removeRoom(string $roomId): mixed
{
    $room->destroy();
}
```

#### GameServer.php 迁移

**v1.x 代码：**
```php
\PfinalClub\Asyncio\create_task(
    $room->onPlayerMessage($player, $event, $payload)
);
```

**v2.0 代码：**
```php
\PfinalClub\Asyncio\create_task(
    fn() => $room->onPlayerMessage($player, $event, $payload)
);
```

### 第三步：更新游戏房间实现

**v1.x 游戏房间：**
```php
class MyGameRoom extends Room
{
    protected function onCreate(): Generator
    {
        $this->broadcast('room:created', ['message' => '房间创建']);
        yield;
    }
    
    protected function onStart(): Generator
    {
        $this->broadcast('game:start', ['message' => '游戏开始']);
        yield sleep(2);
    }
    
    protected function run(): Generator
    {
        for ($round = 1; $round <= 3; $round++) {
            $this->broadcast('game:round', ['round' => $round]);
            yield sleep(5);
        }
        
        $this->broadcast('game:end', ['message' => '游戏结束']);
        yield from $this->destroy();
    }
    
    public function onPlayerMessage(Player $player, string $event, mixed $data): Generator
    {
        if ($event === 'action') {
            // 处理玩家动作
        }
        yield;
    }
}

function main(): Generator
{
    $room = new MyGameRoom('room_001');
    $room->addPlayer(new Player('p1', null, 'Alice'));
    yield from $room->start();
}

run(main());
```

**v2.0 游戏房间：**
```php
class MyGameRoom extends Room
{
    protected function onCreate(): mixed
    {
        $this->broadcast('room:created', ['message' => '房间创建']);
    }
    
    protected function onStart(): mixed
    {
        $this->broadcast('game:start', ['message' => '游戏开始']);
        sleep(2);
    }
    
    protected function run(): mixed
    {
        for ($round = 1; $round <= 3; $round++) {
            $this->broadcast('game:round', ['round' => $round]);
            sleep(5);
        }
        
        $this->broadcast('game:end', ['message' => '游戏结束']);
        $this->destroy();
    }
    
    public function onPlayerMessage(Player $player, string $event, mixed $data): mixed
    {
        if ($event === 'action') {
            // 处理玩家动作
        }
    }
}

function main(): mixed
{
    $room = new MyGameRoom('room_001');
    $room->addPlayer(new Player('p1', null, 'Alice'));
    $room->start();
}

run(main(...));
```

### 第四步：更新并发任务

**v1.x 并发任务：**
```php
protected function run(): Generator
{
    $task1 = create_task($this->taskA());
    $task2 = create_task($this->taskB());
    
    $results = yield gather($task1, $task2);
    
    return $results;
}

private function taskA(): Generator
{
    yield sleep(2);
    return 'Result A';
}

private function taskB(): Generator
{
    yield sleep(1);
    return 'Result B';
}
```

**v2.0 并发任务：**
```php
protected function run(): mixed
{
    $task1 = create_task(fn() => $this->taskA());
    $task2 = create_task(fn() => $this->taskB());
    
    $results = gather($task1, $task2);
    
    return $results;
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

### 第五步：更新测试文件

**v1.x 测试：**
```php
use Generator;

class TestRoom extends Room
{
    protected function run(): Generator
    {
        yield;
    }
}
```

**v2.0 测试：**
```php
class TestRoom extends Room
{
    protected function run(): mixed
    {
        // 空实现用于测试
    }
}
```

## 常见问题和解决方案

### Q1: 为什么需要传入 callable 给 create_task？

**A:** v2.0 的 `create_task` 需要 callable 而不是 Generator，因为 Fiber 是真正的协程，不需要 Generator 的 yield 机制。

```php
// v1.x
create_task($this->someGenerator());

// v2.0
create_task(fn() => $this->someFunction());
```

### Q2: 如何处理异步函数调用？

**A:** v2.0 中直接调用异步函数，不需要 yield：

```php
// v1.x
yield from $this->onCreate();

// v2.0
$this->onCreate();
```

### Q3: 为什么 PHP 版本要求降低了？

**A:** pfinalclub/asyncio v2.0 基于 PHP Fiber，最低要求 PHP 8.1。虽然框架降低了要求，但建议生产环境使用 PHP 8.3+。

### Q4: 性能提升体现在哪里？

**A:** Fiber 比 Generator 快 2-3 倍，主要体现在：
- 更快的协程切换
- 更少的内存开销
- 更精确的定时器
- 更好的错误堆栈

## 迁移检查清单

- [ ] 更新 composer.json 依赖
- [ ] 移除所有 `use Generator;` 导入
- [ ] 更新所有异步方法签名：`Generator` → `mixed`
- [ ] 移除所有 `yield` 和 `yield from` 关键字
- [ ] 更新 `create_task` 调用，传入 callable
- [ ] 更新 `run()` 调用：`run(main())` → `run(main(...))`
- [ ] 更新所有示例和测试文件
- [ ] 运行单元测试验证
- [ ] 更新文档和注释

## 性能对比

| 指标 | v1.x (Generator) | v2.0 (Fiber) | 提升 |
|------|------------------|--------------|------|
| 创建 1000 个任务 | ~6ms | ~2-3ms | 2-3x |
| 5000 并发任务 | ~47ms | ~20-25ms | 2-3x |
| sleep() 精度 | ±1ms | ±0.1ms | 10x |
| await() 延迟 | 1-2ms | <0.1ms | 10-20x |

## 总结

pfinal-asyncio-gamekit v2.0 是一个破坏性升级，需要重写所有异步代码，但带来的性能提升和代码简化是值得的。建议：

1. 先在开发环境完成迁移
2. 充分测试所有功能
3. 对比性能提升效果
4. 更新相关文档和培训材料

如有问题，请参考 [pfinalclub/asyncio v2.0 文档](https://github.com/pfinalclub/pfinal-asyncio) 或提交 Issue。
