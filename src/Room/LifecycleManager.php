<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Room;

use PfinalClub\AsyncioGamekit\Exceptions\RoomException;
use PfinalClub\AsyncioGamekit\Logger\LoggerFactory;

/**
 * 生命周期管理实现类
 */
class LifecycleManager implements LifecycleManagerInterface
{
    /** @var OptimizedRoom 所属房间 */
    protected OptimizedRoom $room;
    
    /** @var string 房间状态 */
    protected string $status = 'waiting'; // waiting, running, finished
    
    /** @var bool 是否正在运行 */
    protected bool $isRunning = false;

    /**
     * @param OptimizedRoom $room 所属房间
     */
    public function __construct(OptimizedRoom $room)
    {
        $this->room = $room;
    }

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
    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->room->broadcast('room:status_changed', [
            'status' => $status,
            'room_id' => $this->room->getId()
        ]);
    }

    /**
     * 检查是否可以开始游戏
     */
    public function canStart(): bool
    {
        $playerCount = $this->room->getPlayerCount();
        $minPlayers = $this->room->getConfig()->get('min_players', 2);
        $maxPlayers = $this->room->getConfig()->get('max_players', 4);
        
        return $playerCount >= $minPlayers 
            && $playerCount <= $maxPlayers
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
            throw RoomException::roomAlreadyStarted($this->room->getId());
        }

        if (!$this->canStart()) {
            $playerCount = $this->room->getPlayerCount();
            $minPlayers = $this->room->getConfig()->get('min_players', 2);
            
            throw RoomException::roomNotReady($this->room->getId(), $playerCount, $minPlayers);
        }

        $this->isRunning = true;
        $this->setStatus('running');

        try {
            $this->room->onCreate();
            $this->room->onStart();
            return $this->room->run();
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
            $this->room->onDestroy();
        } catch (\Throwable $e) {
            LoggerFactory::error("Error in onDestroy for room {room_id}", [
                'room_id' => $this->room->getId(),
                'error' => $e->getMessage(),
            ]);
        }

        // 清除所有定时器
        $this->room->getTimerManager()->clearAllTimers();
        
        return null;
    }
    
    /**
     * 处理房间错误
     */
    protected function handleRoomError(\Throwable $e): void
    {
        LoggerFactory::error("Room {room_id} error: {error}", [
            'room_id' => $this->room->getId(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // 广播错误消息
        $this->room->broadcast('room:error', [
            'error' => $e->getMessage(),
            'room_id' => $this->room->getId()
        ]);
        
        // 将房间状态设置为finished
        $this->setStatus('finished');
        
        // 标记为不再运行
        $this->isRunning = false;
    }
}