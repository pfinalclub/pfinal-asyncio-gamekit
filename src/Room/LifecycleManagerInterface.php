<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Room;

use PfinalClub\AsyncioGamekit\Exceptions\RoomException;

/**
 * 生命周期管理接口
 */
interface LifecycleManagerInterface
{
    /**
     * 获取房间状态
     * @return string
     */
    public function getStatus(): string;
    
    /**
     * 设置房间状态
     * @param string $status
     * @return void
     */
    public function setStatus(string $status): void;
    
    /**
     * 检查是否可以开始游戏
     * @return bool
     */
    public function canStart(): bool;
    
    /**
     * 启动房间游戏逻辑
     * @return mixed
     * @throws RoomException
     */
    public function start(): mixed;
    
    /**
     * 销毁房间
     * @return mixed
     */
    public function destroy(): mixed;
}