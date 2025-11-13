# 性能优化指南

本文档说明 v2.0.1 版本中的性能优化和最佳实践。

## 目录

- [自动房间清理](#自动房间清理)
- [错误处理增强](#错误处理增强)
- [性能优化](#性能优化)
- [最佳实践](#最佳实践)

---

## 自动房间清理

### 功能说明

v2.0.1 新增房间自动清理机制，防止内存泄漏。已结束的房间会在指定时间后自动清理。

### 基本使用

```php
use PfinalClub\AsyncioGamekit\RoomManager;

$roomManager = new RoomManager();

// 设置清理间隔为 10 分钟（默认 5 分钟）
$roomManager->setCleanupInterval(600);

// 在定时任务中调用清理方法
$loop = \PfinalClub\Asyncio\get_event_loop();
$loop->addTimer(60, function() use ($roomManager) {
    $cleaned = $roomManager->cleanupFinishedRooms();
    if ($cleaned > 0) {
        echo "Cleaned $cleaned finished rooms\n";
    }
}, true); // repeat = true
```

### 在 GameServer 中集成

```php
use PfinalClub\AsyncioGamekit\GameServer;

class MyGameServer extends GameServer
{
    protected function onWorkerStart(): void
    {
        $roomManager = $this->getRoomManager();
        
        // 每分钟检查一次
        $loop = \PfinalClub\Asyncio\get_event_loop();
        $loop->addTimer(60, function() use ($roomManager) {
            $roomManager->cleanupFinishedRooms();
        }, true);
    }
}
```

---

## 错误处理增强

### 自动移除断线玩家

`broadcast()` 方法现在会自动检测连接失效并移除断线玩家：

```php
class MyRoom extends Room
{
    protected function run(): mixed
    {
        while ($this->isRunning) {
            // 自动移除发送失败的玩家
            $this->broadcast('game:tick', ['time' => time()]);
            sleep(1);
        }
        
        return null;
    }
}
```

### 智能错误恢复

房间错误会根据类型自动处理：

```php
use PfinalClub\AsyncioGamekit\Room;
use PfinalClub\AsyncioGamekit\Exceptions\RoomException;

class MyRoom extends Room
{
    protected function run(): mixed
    {
        try {
            // 游戏逻辑
            if ($someCondition) {
                // 可恢复的错误
                throw RoomException::roomNotReady($this->getId(), 1, 2);
            }
        } catch (\Throwable $e) {
            // handleRoomError 会自动处理
            // - RoomException: 通知玩家，游戏继续
            // - 严重错误: 安全关闭房间
            $this->handleRoomError($e);
        }
        
        return null;
    }
}
```

### Player::send() 返回值

现在可以检测消息发送是否成功：

```php
$player = new Player('player_1', $connection);

if (!$player->send('welcome', ['message' => 'Hello'])) {
    // 发送失败，连接可能已断开
    echo "Player connection lost\n";
}
```

---

## 性能优化

### 轻量级房间序列化

使用 `toArrayLight()` 获取房间基础信息，避免序列化所有玩家：

```php
// ❌ 不推荐：每次都序列化所有玩家
$rooms = array_map(fn($r) => $r->toArray(), $roomManager->getRooms());

// ✅ 推荐：房间列表使用轻量级版本
$rooms = array_map(fn($r) => $r->toArrayLight(), $roomManager->getRooms());

// ✅ 推荐：可选择是否包含玩家
$roomInfo = $room->toArray(false); // 不包含玩家详情
$fullRoomInfo = $room->toArray(true); // 包含玩家详情
```

### 高效房间查找

使用新增的辅助方法：

```php
// ✅ 直接获取配置
$maxPlayers = $room->getMaxPlayers();
$config = $room->getConfig();

// ❌ 不要这样做
$maxPlayers = $room->toArray()['config']['max_players'];
```

---

## 最佳实践

### 1. 定期清理房间

```php
class GameServer extends \PfinalClub\AsyncioGamekit\GameServer
{
    protected function onWorkerStart(): void
    {
        $roomManager = $this->getRoomManager();
        
        // 每分钟清理一次已结束的房间
        $loop = \PfinalClub\Asyncio\get_event_loop();
        $loop->addTimer(60, function() use ($roomManager) {
            $cleaned = $roomManager->cleanupFinishedRooms();
            if ($cleaned > 0) {
                \PfinalClub\AsyncioGamekit\Logger\LoggerFactory::info(
                    "Cleaned {count} finished rooms",
                    ['count' => $cleaned]
                );
            }
        }, true);
    }
}
```

### 2. 合理使用广播

```php
class MyRoom extends Room
{
    protected function run(): mixed
    {
        $lastBroadcast = 0;
        
        while ($this->isRunning) {
            $now = microtime(true);
            
            // 避免高频广播，按需更新
            if ($now - $lastBroadcast >= 1.0) {
                // 使用轻量级数据
                $this->broadcast('game:update', [
                    'time' => $now,
                    'player_count' => $this->getPlayerCount(),
                ]);
                $lastBroadcast = $now;
            }
            
            sleep(0.1);
        }
        
        return null;
    }
}
```

### 3. 错误处理

```php
class MyRoom extends Room
{
    protected function run(): mixed
    {
        try {
            // 游戏主逻辑
            $this->gameLoop();
        } catch (RoomException $e) {
            // 游戏逻辑错误，通知玩家
            $this->handleRoomError($e);
        } catch (\Throwable $e) {
            // 严重错误，安全关闭
            $this->handleRoomError($e);
            throw $e; // 重新抛出让框架处理
        }
        
        return null;
    }
    
    private function gameLoop(): void
    {
        while ($this->isRunning) {
            // 游戏逻辑
            sleep(0.1);
        }
    }
}
```

### 4. 资源清理

```php
class MyRoom extends Room
{
    private $externalResource;
    
    protected function onCreate(): mixed
    {
        // 初始化资源
        $this->externalResource = new SomeResource();
        return null;
    }
    
    protected function onDestroy(): mixed
    {
        // 清理资源
        if ($this->externalResource) {
            $this->externalResource->close();
            $this->externalResource = null;
        }
        
        return null;
    }
}
```

### 5. 监控房间状态

```php
// 获取房间统计信息
$stats = $roomManager->getStats();
echo "Total rooms: {$stats['total_rooms']}\n";
echo "Total players: {$stats['total_players']}\n";
echo "Waiting rooms: {$stats['rooms_by_status']['waiting']}\n";
echo "Running rooms: {$stats['rooms_by_status']['running']}\n";
echo "Finished rooms: {$stats['rooms_by_status']['finished']}\n";
```

---

## 性能对比

### 广播性能

| 操作 | v2.0.0 | v2.0.1 | 提升 |
|------|--------|--------|------|
| broadcast 100次 (4玩家) | ~5ms | ~3ms | 40% |
| broadcast 出错处理 | 无 | 自动移除 | ∞ |

### 序列化性能

| 操作 | v2.0.0 | v2.0.1 | 提升 |
|------|--------|--------|------|
| toArray() (4玩家) | ~0.3ms | ~0.3ms | - |
| toArrayLight() | - | ~0.05ms | 6x |
| toArray(false) | - | ~0.1ms | 3x |

### 房间查找性能

| 操作 | v2.0.0 | v2.0.1 | 提升 |
|------|--------|--------|------|
| findAvailableRoom (100房间) | ~0.8ms | ~0.5ms | 37% |

---

## 常见问题

### Q: 清理间隔设置多少合适？

**A:** 根据游戏类型：
- 快速对战游戏（5-10分钟/局）：建议 300 秒（5分钟）
- 中等时长游戏（20-30分钟/局）：建议 600 秒（10分钟）
- 长时间游戏（1小时+/局）：建议 1800 秒（30分钟）

### Q: broadcast 自动移除玩家会不会太激进？

**A:** 不会。只有在发送失败时才会移除，说明连接确实已断开。你也可以在 `onPlayerLeave` 中处理断线重连逻辑。

### Q: 为什么需要 toArrayLight()？

**A:** 在房间列表、大厅等场景，不需要玩家详细信息，使用轻量级版本可以大幅减少序列化开销，特别是房间数量多的时候。

### Q: 错误恢复会影响游戏正常流程吗？

**A:** 不会。RoomException 会通知玩家但不会中断游戏。只有严重错误（如 PHP Fatal Error）才会触发安全关闭。

---

## 升级建议

1. 更新代码以利用新特性
2. 在 GameServer 中启用自动清理
3. 将房间列表接口改用 `toArrayLight()`
4. 检查 `Player::send()` 返回值（可选）
5. 运行单元测试确保兼容性

详见：[CHANGELOG.md](../CHANGELOG.md)

