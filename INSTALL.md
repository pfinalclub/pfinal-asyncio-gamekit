# 安装指南

## 系统要求

- **PHP**: >= 8.3
- **Composer**: 最新版本
- **扩展**: 
  - `pcntl` (Linux/Mac)
  - `posix` (Linux/Mac)
  - `sockets` (可选，但推荐)

## 快速安装

### 1. 通过 Composer 安装

```bash
composer require pfinalclub/asyncio-gamekit
```

### 2. 从源码安装

```bash
# 克隆仓库
git clone https://github.com/pfinalclub/asyncio-gamekit.git
cd asyncio-gamekit

# 安装依赖
composer install
```

## 检查环境

### PHP 版本检查

```bash
php -v
```

应该显示 PHP 8.3 或更高版本。

### PHP 扩展检查

```bash
php -m | grep -E "pcntl|posix|sockets"
```

应该显示这些扩展已安装。

### Composer 检查

```bash
composer --version
```

## 验证安装

### 运行测试示例

```bash
php examples/SimpleGame.php
```

如果看到倒计时输出，说明安装成功！

### 启动 WebSocket 服务器

```bash
php examples/WebSocketServer.php
```

然后在浏览器打开 `examples/client.html`，应该能成功连接。

## 常见问题

### Q: 在 Windows 上可以使用吗？

A: 可以，但某些功能可能受限。Windows 不支持 `pcntl` 和 `posix` 扩展，因此多进程功能无法使用。建议在 Linux 或 Mac 上运行生产环境。

**Windows 解决方案：**
```php
$server = new GameServer('0.0.0.0', 2345, [
    'count' => 1,  // Windows 上只能使用单进程
]);
```

### Q: Composer 安装失败？

A: 尝试以下步骤：

1. 更新 Composer
```bash
composer self-update
```

2. 清除缓存
```bash
composer clear-cache
```

3. 使用国内镜像（中国用户）
```bash
composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/
```

### Q: PHP 版本不够？

A: 需要升级 PHP 到 8.3 或更高版本。

**Ubuntu/Debian:**
```bash
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install php8.3-cli php8.3-pcntl
```

**Mac:**
```bash
brew install php@8.3
```

**Windows:**
下载最新 PHP: https://windows.php.net/download/

### Q: 缺少扩展？

**Linux:**
```bash
# 安装 pcntl 和 posix（通常自带）
sudo apt install php8.3-pcntl php8.3-posix

# 安装 sockets
sudo apt install php8.3-sockets
```

**Mac:**
```bash
# 通常自带所需扩展
# 如果需要，可以通过 brew 安装
brew install php@8.3
```

## 开发环境配置

### VSCode 配置

创建 `.vscode/settings.json`:

```json
{
    "php.validate.executablePath": "/usr/bin/php",
    "php.suggest.basic": true,
    "editor.formatOnSave": true
}
```

### PHPStorm 配置

1. 设置 PHP 版本: Settings → PHP → Language Level → 8.3
2. 配置 Composer: Settings → PHP → Composer
3. 启用代码提示: Settings → Editor → Inspections → PHP

## 生产环境部署

### 1. 使用 Supervisor 管理进程

创建 `/etc/supervisor/conf.d/gameserver.conf`:

```ini
[program:gameserver]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/WebSocketServer.php
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/gameserver.log
```

启动：
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start gameserver:*
```

### 2. 使用 Systemd

创建 `/etc/systemd/system/gameserver.service`:

```ini
[Unit]
Description=Game Server
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/path/to/your/project
ExecStart=/usr/bin/php WebSocketServer.php
Restart=on-failure

[Install]
WantedBy=multi-user.target
```

启动：
```bash
sudo systemctl daemon-reload
sudo systemctl enable gameserver
sudo systemctl start gameserver
```

### 3. 使用 Docker

创建 `Dockerfile`:

```dockerfile
FROM php:8.3-cli

# 安装扩展
RUN docker-php-ext-install pcntl posix sockets

# 安装 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 复制项目文件
WORKDIR /app
COPY . /app

# 安装依赖
RUN composer install --no-dev --optimize-autoloader

# 暴露端口
EXPOSE 2345

# 启动服务器
CMD ["php", "examples/WebSocketServer.php"]
```

构建和运行：
```bash
docker build -t gameserver .
docker run -d -p 2345:2345 gameserver
```

## 性能优化

### 1. OPcache 配置

在 `php.ini` 中启用 OPcache:

```ini
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

### 2. 调整 Worker 数量

```php
$server = new GameServer('0.0.0.0', 2345, [
    'count' => 4,  // 根据 CPU 核心数调整
]);
```

建议设置为 CPU 核心数。

### 3. 禁用调试模式

生产环境中：

```php
use Workerman\Worker;

Worker::$daemonize = true;  // 守护进程模式
Worker::$stdoutFile = '/tmp/stdout.log';  // 日志文件
```

## 获取帮助

如果遇到问题：

1. 查看 [README.md](README.md)
2. 查看 [docs/GUIDE.md](docs/GUIDE.md)
3. 提交 Issue: https://github.com/pfinalclub/asyncio-gamekit/issues

## 下一步

安装完成后，建议：

1. 阅读 [README.md](README.md) 了解框架概述
2. 运行示例代码熟悉框架
3. 查看 [docs/API.md](docs/API.md) 学习 API
4. 查看 [docs/GUIDE.md](docs/GUIDE.md) 学习最佳实践

开始创建你的游戏吧！🎮

