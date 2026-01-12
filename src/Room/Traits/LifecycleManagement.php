<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Room\Traits;

use PfinalClub\AsyncioGamekit\Exceptions\RoomException;
use PfinalClub\AsyncioGamekit\Logger\LoggerFactory;

/**
 * LifecycleManagement Trait
 * 负责房间生命周期管理
 */
trait LifecycleManagement
{
    /** @var string 房间状态 */
    protected string $status = 'waiting'; // waiting, running, finished
    
    /** @var bool 是否正在运行 */
    protected bool $isRunning = false;

    /**
     * 获取房间状态
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * 设置房间状态
     */
    protected function setStatus(string $status): void
    {
        $oldStatus = $this->status;
        $this->status = $status;
        
        // 清除缓存（状态已变化）
        $this->invalidateCache();
        
        // 通知观察者
        $this->notifyStatusChanged($oldStatus, $status);
        
        $this->broadcast('room:status_changed', [
            'status' => $status,
            'room_id' => $this->id
        ]);
    }

    /**
     * 检查是否可以开始游戏
     */
    public function canStart(): bool
    {
        $playerCount = count($this->players);
        return $playerCount >= $this->cachedMinPlayers 
            && $playerCount <= $this->cachedMaxPlayers
            && !$this->isRunning;
    }

    /**
     * 启动房间游戏逻辑
     * 
     * @throws RoomException
     */
    public function start(): mixed
    {
        if ($this->isRunning) {
            throw RoomException::roomAlreadyStarted($this->id);
        }

        if (!$this->canStart()) {
            $playerCount = count($this->players);
            throw RoomException::roomNotReady($this->id, $playerCount, $this->cachedMinPlayers);
        }

        $this->isRunning = true;
        $this->setStatus('running');

        try {
            $this->onCreate();
            $this->onStart();
            return $this->run();
        } catch (\Throwable $e) {
            $this->handleRoomError($e);
            throw $e;
        }
    }

    /**
     * 销毁房间
     */
    public function destroy(): mixed
    {
        if ($this->isRunning) {
            $this->isRunning = false;
        }

        try {
            $this->onDestroy();
        } catch (\Throwable $e) {
            LoggerFactory::error("Error in onDestroy for room {room_id}", [
                'room_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }

        // 清除所有定时器
        $this->clearAllTimers();

        // 移除所有玩家
        foreach ($this->players as $player) {
            $player->setRoom(null);
        }
        $this->players = [];

        // 清理自定义数据
        $this->clearData();

        $this->setStatus('finished');
        
        return null;
    }

    /**
     * 房间创建时调用（子类可重写）
     */
    protected function onCreate(): mixed
    {
        return null;
    }

    /**
     * 游戏开始时调用（子类可重写）
     */
    protected function onStart(): mixed
    {
        return null;
    }

    /**
     * 游戏主循环（子类必须实现）
     */
    abstract protected function run(): mixed;

    /**
     * 房间销毁时调用（子类可重写）
     */
    protected function onDestroy(): mixed
    {
        return null;
    }

    /**
     * 处理房间运行时错误
     */
    protected function handleRoomError(\Throwable $e): void
    {
        // 记录错误
        LoggerFactory::error("Room error in {room_id}: {message}", [
            'room_id' => $this->id,
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        // 根据错误类型采取不同策略
        if ($e instanceof RoomException) {
            // 房间异常，通知玩家具体错误
            $this->broadcast('room:error', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'recoverable' => true,
            ]);
        } else {
            // 严重错误，尝试恢复
            $this->broadcast('room:error', [
                'message' => 'A serious error occurred in the game',
                'recoverable' => false,
            ]);
            
            // 尝试安全关闭房间
            try {
                LoggerFactory::warning("Attempting to gracefully close room {room_id}", [
                    'room_id' => $this->id,
                ]);
                
                $this->isRunning = false;
                $this->setStatus('finished');
                
                // 延迟销毁，给玩家时间接收错误消息
                \PfinalClub\Asyncio\create_task(function() {
                    \PfinalClub\Asyncio\sleep(2);
                    $this->destroy();
                });
            } catch (\Throwable $recoveryError) {
                // 恢复失败，记录严重错误
                LoggerFactory::critical("Failed to recover room {room_id}", [
                    'room_id' => $this->id,
                    'error' => $recoveryError->getMessage(),
                    'trace' => $recoveryError->getTraceAsString(),
                ]);
            }
        }
    }
}

