#!/bin/bash

# pfinal-asyncio-gamekit 快速启动脚本

echo "=========================================="
echo "  pfinal-asyncio-gamekit"
echo "  异步游戏框架快速启动"
echo "=========================================="
echo ""

# 检查 PHP 版本
php_version=$(php -r "echo PHP_VERSION;")
echo "✓ PHP 版本: $php_version"

# 检查 composer
if ! command -v composer &> /dev/null; then
    echo "✗ Composer 未安装，请先安装 Composer"
    exit 1
fi
echo "✓ Composer 已安装"

# 安装依赖
if [ ! -d "vendor" ]; then
    echo ""
    echo "正在安装依赖..."
    composer install
    if [ $? -ne 0 ]; then
        echo "✗ 依赖安装失败"
        exit 1
    fi
    echo "✓ 依赖安装完成"
fi

echo ""
echo "=========================================="
echo "请选择要运行的示例："
echo "=========================================="
echo "1. 简单倒计时游戏 (SimpleGame.php)"
echo "2. 卡牌游戏 (CardGame.php)"
echo "3. WebSocket 游戏服务器 (WebSocketServer.php)"
echo "0. 退出"
echo ""
read -p "请输入选项 [0-3]: " choice

case $choice in
    1)
        echo ""
        echo "启动简单倒计时游戏..."
        echo "----------------------------------------"
        php examples/SimpleGame.php
        ;;
    2)
        echo ""
        echo "启动卡牌游戏..."
        echo "----------------------------------------"
        php examples/CardGame.php
        ;;
    3)
        echo ""
        echo "启动 WebSocket 游戏服务器..."
        echo "----------------------------------------"
        echo "服务器地址: ws://0.0.0.0:2345"
        echo "在浏览器中打开 examples/client.html 连接服务器"
        echo "按 Ctrl+C 停止服务器"
        echo "----------------------------------------"
        php examples/WebSocketServer.php
        ;;
    0)
        echo "退出"
        exit 0
        ;;
    *)
        echo "无效的选项"
        exit 1
        ;;
esac

