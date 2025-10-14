@echo off
chcp 65001 >nul
cls

echo ==========================================
echo   pfinal-asyncio-gamekit
echo   异步游戏框架快速启动
echo ==========================================
echo.

REM 检查 PHP
php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo × PHP 未安装或未添加到 PATH
    pause
    exit /b 1
)
echo √ PHP 已安装

REM 检查 Composer
composer --version >nul 2>&1
if %errorlevel% neq 0 (
    echo × Composer 未安装或未添加到 PATH
    pause
    exit /b 1
)
echo √ Composer 已安装

REM 安装依赖
if not exist "vendor" (
    echo.
    echo 正在安装依赖...
    composer install
    if %errorlevel% neq 0 (
        echo × 依赖安装失败
        pause
        exit /b 1
    )
    echo √ 依赖安装完成
)

echo.
echo ==========================================
echo 请选择要运行的示例：
echo ==========================================
echo 1. 简单倒计时游戏 (SimpleGame.php)
echo 2. 卡牌游戏 (CardGame.php)
echo 3. WebSocket 游戏服务器 (WebSocketServer.php)
echo 0. 退出
echo.
set /p choice=请输入选项 [0-3]: 

if "%choice%"=="1" (
    echo.
    echo 启动简单倒计时游戏...
    echo ----------------------------------------
    php examples\SimpleGame.php
    pause
) else if "%choice%"=="2" (
    echo.
    echo 启动卡牌游戏...
    echo ----------------------------------------
    php examples\CardGame.php
    pause
) else if "%choice%"=="3" (
    echo.
    echo 启动 WebSocket 游戏服务器...
    echo ----------------------------------------
    echo 服务器地址: ws://0.0.0.0:2345
    echo 在浏览器中打开 examples\client.html 连接服务器
    echo 按 Ctrl+C 停止服务器
    echo ----------------------------------------
    php examples\WebSocketServer.php
) else if "%choice%"=="0" (
    echo 退出
    exit /b 0
) else (
    echo 无效的选项
    pause
    exit /b 1
)

