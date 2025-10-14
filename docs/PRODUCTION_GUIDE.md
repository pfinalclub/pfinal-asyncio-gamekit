# 生产环境部署指南

本文档提供 pfinal-asyncio-gamekit 在生产环境中部署和运维的最佳实践。

---

## 🚀 部署准备

### 系统要求

- **操作系统**: Linux (推荐 Ubuntu 20.04+ / CentOS 7+)
- **PHP 版本**: >= 8.3
- **内存**: 最低 512MB，推荐 2GB+
- **CPU**: 最低 1核，推荐 4核+

### 必要扩展

```bash
# Ubuntu/Debian
sudo apt-get install php8.3-cli php8.3-pcntl php8.3-posix

# CentOS/RHEL
sudo yum install php83-cli php83-pcntl php83-posix
```

---

## 📦 安装部署

### 1. 下载代码

```bash
cd /var/www
git clone https://github.com/your-org/your-game.git
cd your-game
```

### 2. 安装依赖

```bash
composer install --no-dev --optimize-autoloader
```

### 3. 配置环境

```bash
# 复制生产环境配置
cp config/logger.production.php config/logger.php

# 创建必要目录
mkdir -p logs runtime storage
chmod 755 logs runtime storage
```

### 4. 配置文件

编辑 `config/server.php`:

```php
return [
    'host' => '0.0.0.0',
    'port' => 2345,
    
    'worker' => [
        'name' => 'GameServer',
        'count' => 4,  // 设置为 CPU 核心数
        'protocol' => 'websocket',
    ],
    
    'workerman' => [
        'daemonize' => true,  // 守护进程模式
        'log_file' => '/var/log/gamekit/workerman.log',
        'stdout_file' => '/var/log/gamekit/stdout.log',
        'pid_file' => '/var/run/gameserver.pid',
    ],
];
```

---

## 🔧 进程管理

### 使用 Workerman 内置管理

```bash
# 启动
php server.php start -d

# 停止
php server.php stop

# 重启
php server.php restart

# 平滑重启
php server.php reload

# 查看状态
php server.php status
```

### 使用 Systemd

创建服务文件 `/etc/systemd/system/gameserver.service`:

```ini
[Unit]
Description=Game Server
After=network.target

[Service]
Type=forking
User=www-data
Group=www-data
WorkingDirectory=/var/www/your-game
ExecStart=/usr/bin/php /var/www/your-game/server.php start -d
ExecStop=/usr/bin/php /var/www/your-game/server.php stop
ExecReload=/usr/bin/php /var/www/your-game/server.php reload
Restart=on-failure
RestartSec=10s

[Install]
WantedBy=multi-user.target
```

管理服务:

```bash
# 启动
sudo systemctl start gameserver

# 停止
sudo systemctl stop gameserver

# 重启
sudo systemctl restart gameserver

# 开机自启
sudo systemctl enable gameserver

# 查看状态
sudo systemctl status gameserver
```

### 使用 Supervisor

安装 Supervisor:

```bash
sudo apt-get install supervisor
```

创建配置文件 `/etc/supervisor/conf.d/gameserver.conf`:

```ini
[program:gameserver]
command=/usr/bin/php /var/www/your-game/server.php start
directory=/var/www/your-game
user=www-data
autostart=true
autorestart=true
startretries=3
stderr_logfile=/var/log/gameserver.err.log
stdout_logfile=/var/log/gameserver.out.log
```

管理服务:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start gameserver
sudo supervisorctl status gameserver
```

---

## 📝 日志管理

### 日志配置

在 `config/logger.production.php` 中:

```php
return [
    'min_level' => LogLevel::WARNING,
    
    'console' => [
        'enabled' => false,  // 生产环境关闭控制台输出
    ],
    
    'file' => [
        'enabled' => true,
        'path' => '/var/log/gamekit/game.log',
        'max_size' => 50 * 1024 * 1024,  // 50MB
        'max_files' => 10,
    ],
];
```

### 日志轮转

使用 logrotate 管理日志:

创建 `/etc/logrotate.d/gamekit`:

```
/var/log/gamekit/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 0644 www-data www-data
    postrotate
        # 平滑重启服务
        /usr/bin/php /var/www/your-game/server.php reload
    endscript
}
```

---

## 🔒 安全配置

### 防火墙设置

```bash
# 允许游戏端口
sudo ufw allow 2345/tcp

# 如果使用 SSL
sudo ufw allow 443/tcp
```

### SSL/TLS 配置

使用 Nginx 反向代理:

```nginx
upstream gameserver {
    server 127.0.0.1:2345;
}

server {
    listen 443 ssl;
    server_name game.example.com;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    location / {
        proxy_pass http://gameserver;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_read_timeout 86400;
    }
}
```

---

## 📊 性能优化

### Worker 进程数

```php
'worker' => [
    'count' => 4,  // 建议设置为 CPU 核心数
],
```

### PHP 配置

编辑 `php.ini`:

```ini
; 内存限制
memory_limit = 512M

; 执行时间
max_execution_time = 0

; OPcache 配置
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
```

### Redis 持久化

```php
use PfinalClub\AsyncioGamekit\Persistence\RedisAdapter;
use PfinalClub\AsyncioGamekit\Persistence\RoomStateManager;

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$adapter = new RedisAdapter($redis, 'game:');
$stateManager = new RoomStateManager($adapter);
```

---

## 📈 监控和告警

### 基础监控

创建监控脚本 `monitor.php`:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use PfinalClub\AsyncioGamekit\Logger\LoggerFactory;

// 检查进程
$pidFile = '/var/run/gameserver.pid';
if (!file_exists($pidFile)) {
    LoggerFactory::critical('PID file not found', ['file' => $pidFile]);
    exit(1);
}

$pid = (int)file_get_contents($pidFile);
if (!posix_kill($pid, 0)) {
    LoggerFactory::critical('Process not running', ['pid' => $pid]);
    exit(1);
}

// 检查内存
$memInfo = file_get_contents("/proc/$pid/status");
preg_match('/VmRSS:\s+(\d+)/', $memInfo, $matches);
$memoryMB = $matches[1] / 1024;

if ($memoryMB > 1024) {  // 超过 1GB 告警
    LoggerFactory::warning('High memory usage', ['memory_mb' => $memoryMB]);
}

LoggerFactory::info('Health check passed', [
    'pid' => $pid,
    'memory_mb' => $memoryMB,
]);
```

添加 crontab:

```bash
# 每 5 分钟检查一次
*/5 * * * * /usr/bin/php /var/www/your-game/monitor.php
```

### Prometheus 集成

```php
// 暴露 metrics 端点
use Workerman\Worker;

$metricsWorker = new Worker('http://0.0.0.0:9090');
$metricsWorker->name = 'metrics';
$metricsWorker->count = 1;

$metricsWorker->onMessage = function($connection) use ($roomManager) {
    $stats = $roomManager->getStats();
    
    $metrics = "# HELP game_rooms_total Total number of rooms\n";
    $metrics .= "# TYPE game_rooms_total gauge\n";
    $metrics .= "game_rooms_total {$stats['total_rooms']}\n\n";
    
    $metrics .= "# HELP game_players_total Total number of players\n";
    $metrics .= "# TYPE game_players_total gauge\n";
    $metrics .= "game_players_total {$stats['total_players']}\n";
    
    $connection->send($metrics);
};
```

---

## 🔄 备份和恢复

### 状态备份

```bash
#!/bin/bash
# backup.sh

BACKUP_DIR="/backup/gamekit"
DATE=$(date +%Y%m%d_%H%M%S)

# 备份 Redis 数据
redis-cli --rdb "$BACKUP_DIR/redis_$DATE.rdb"

# 备份文件存储
tar -czf "$BACKUP_DIR/storage_$DATE.tar.gz" /var/www/your-game/storage

# 删除 7 天前的备份
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup completed: $DATE"
```

添加 crontab:

```bash
# 每天凌晨 2 点备份
0 2 * * * /var/www/your-game/backup.sh
```

---

## 🐛 故障排查

### 常见问题

1. **端口被占用**
   ```bash
   # 查找占用端口的进程
   lsof -i :2345
   ```

2. **权限问题**
   ```bash
   # 确保目录权限正确
   chown -R www-data:www-data /var/www/your-game
   chmod -R 755 /var/www/your-game
   ```

3. **内存泄漏**
   ```bash
   # 查看进程内存
   ps aux | grep gameserver
   
   # 定期重启（临时方案）
   0 4 * * * /usr/bin/php /var/www/your-game/server.php restart
   ```

### 日志分析

```bash
# 查看错误日志
tail -f /var/log/gamekit/game.log | grep ERROR

# 统计连接数
tail -f /var/log/gamekit/game.log | grep "Player connected" | wc -l

# 分析性能
tail -f /var/log/gamekit/game.log | grep "slow"
```

---

## 📋 检查清单

### 上线前

- [ ] 生产环境配置文件已更新
- [ ] 日志路径和权限正确
- [ ] 进程管理工具已配置
- [ ] 防火墙规则已设置
- [ ] SSL 证书已配置（如需要）
- [ ] 监控和告警已部署
- [ ] 备份策略已实施
- [ ] 负载测试已完成

### 上线后

- [ ] 进程状态正常
- [ ] 日志输出正常
- [ ] 性能指标正常
- [ ] 客户端连接正常
- [ ] 监控数据正常

---

## 📞 支持

遇到问题？

- 查看 [文档](../README.md)
- 提交 [Issue](https://github.com/pfinalclub/pfinal-asyncio-gamekit/issues)
- 联系技术支持

---

**更新日期：** 2024-10-14  
**版本：** 1.0.0

