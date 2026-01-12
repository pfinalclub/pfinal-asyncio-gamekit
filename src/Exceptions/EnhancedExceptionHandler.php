<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Exceptions;

use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\RoomManager;
use PfinalClub\AsyncioGamekit\Logger\LoggerFactory;

/**
 * 增强异常处理器
 * 
 * 提供更完善的异常处理和资源清理机制
 */
class EnhancedExceptionHandler
{
    /** @var RoomManager 房间管理器 */
    private RoomManager $roomManager;
    
    /** @var array 异常统计信息 */
    private array $exceptionStats = [
        'total' => 0,
        'by_type' => [],
        'by_player' => [],
        'recent' => []
    ];
    
    /** @var int 最大最近异常记录数 */
    private const MAX_RECENT_EXCEPTIONS = 100;
    
    /** @var array 需要清理的资源类型 */
    private array $resourceTypes = [
        'player_connections',
        'room_instances',
        'timer_handles',
        'memory_cache'
    ];

    /**
     * 构造函数
     */
    public function __construct(RoomManager $roomManager)
    {
        $this->roomManager = $roomManager;
    }

    /**
     * 处理游戏异常
     */
    public function handleGameException(Player $player, GameException $e): void
    {
        $this->recordException($e, $player);
        
        // 根据异常类型采取不同措施
        switch (get_class($e)) {
            case RoomException::class:
                $this->handleRoomException($player, $e);
                break;
            case ServerException::class:
                $this->handleServerException($player, $e);
                break;
            case PermissionException::class:
                $this->handlePermissionException($player, $e);
                break;
            default:
                $this->handleGenericGameException($player, $e);
        }
        
        $this->logException($e);
    }

    /**
     * 处理房间异常
     */
    private function handleRoomException(Player $player, RoomException $e): void
    {
        $errorData = [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'context' => $e->getContext()
        ];
        
        // 如果是房间不存在异常，清理相关资源
        if ($e->getCode() === RoomException::ROOM_NOT_FOUND) {
            $this->cleanupPlayerResources($player);
        }
        
        $player->send(GameEvents::ERROR, $errorData);
    }

    /**
     * 处理服务器异常
     */
    private function handleServerException(Player $player, ServerException $e): void
    {
        $errorData = [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'context' => $e->getContext()
        ];
        
        // 如果是安全违规，可能需要断开连接
        if ($e->getCode() === ServerException::SECURITY_VIOLATION) {
            $this->handleSecurityViolation($player, $e);
        }
        
        $player->send(GameEvents::ERROR, $errorData);
    }

    /**
     * 处理权限异常
     */
    private function handlePermissionException(Player $player, PermissionException $e): void
    {
        $errorData = [
            'message' => 'Permission denied',
            'code' => $e->getCode(),
            'context' => $e->getContext()
        ];
        
        $player->send(GameEvents::ERROR, $errorData);
    }

    /**
     * 处理通用游戏异常
     */
    private function handleGenericGameException(Player $player, GameException $e): void
    {
        $errorData = [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'context' => $e->getContext()
        ];
        
        $player->send(GameEvents::ERROR, $errorData);
    }

    /**
     * 处理安全违规
     */
    private function handleSecurityViolation(Player $player, ServerException $e): void
    {
        // 记录安全事件
        LoggerFactory::warning("Security violation detected", [
            'player_id' => $player->getId(),
            'exception' => $e->getMessage(),
            'context' => $e->getContext()
        ]);
        
        // 断开连接
        $this->disconnectPlayer($player, 'Security violation');
    }

    /**
     * 处理通用异常
     */
    public function handleGenericException(Player $player, \Throwable $e): void
    {
        $this->recordException($e, $player);
        
        // 发送通用错误消息
        $player->send(GameEvents::ERROR, [
            'message' => 'Internal server error',
            'code' => 500
        ]);
        
        $this->logException($e);
        
        // 如果是严重错误，可能需要清理资源
        if ($this->isCriticalException($e)) {
            $this->handleCriticalException($player, $e);
        }
    }

    /**
     * 清理玩家资源
     */
    private function cleanupPlayerResources(Player $player): void
    {
        try {
            // 从房间中移除玩家
            $this->roomManager->leaveRoom($player);
            
            // 清理玩家相关资源
            $player->cleanup();
            
            LoggerFactory::info("Player resources cleaned up", [
                'player_id' => $player->getId()
            ]);
        } catch (\Throwable $e) {
            LoggerFactory::error("Failed to cleanup player resources", [
                'player_id' => $player->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 断开玩家连接
     */
    private function disconnectPlayer(Player $player, string $reason): void
    {
        try {
            // 清理资源
            $this->cleanupPlayerResources($player);
            
            // 发送断开连接消息
            $player->send(GameEvents::DISCONNECTED, [
                'reason' => $reason
            ]);
            
            // 关闭连接（如果支持）
            if (method_exists($player->getConnection(), 'close')) {
                $player->getConnection()->close();
            }
            
            LoggerFactory::info("Player disconnected", [
                'player_id' => $player->getId(),
                'reason' => $reason
            ]);
        } catch (\Throwable $e) {
            LoggerFactory::error("Failed to disconnect player", [
                'player_id' => $player->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 处理严重异常
     */
    private function handleCriticalException(Player $player, \Throwable $e): void
    {
        LoggerFactory::error("Critical exception occurred", [
            'player_id' => $player->getId(),
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // 断开连接
        $this->disconnectPlayer($player, 'Critical error');
    }

    /**
     * 记录异常统计
     */
    private function recordException(\Throwable $e, ?Player $player = null): void
    {
        $exceptionType = get_class($e);
        
        // 更新统计信息
        $this->exceptionStats['total']++;
        $this->exceptionStats['by_type'][$exceptionType] = 
            ($this->exceptionStats['by_type'][$exceptionType] ?? 0) + 1;
        
        if ($player) {
            $playerId = $player->getId();
            $this->exceptionStats['by_player'][$playerId] = 
                ($this->exceptionStats['by_player'][$playerId] ?? 0) + 1;
        }
        
        // 记录最近异常
        $this->exceptionStats['recent'][] = [
            'type' => $exceptionType,
            'message' => $e->getMessage(),
            'player_id' => $player ? $player->getId() : null,
            'timestamp' => time()
        ];
        
        // 限制最近异常记录数量
        if (count($this->exceptionStats['recent']) > self::MAX_RECENT_EXCEPTIONS) {
            array_shift($this->exceptionStats['recent']);
        }
    }

    /**
     * 记录异常日志
     */
    private function logException(\Throwable $e): void
    {
        $logContext = [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
        
        if ($e instanceof GameException) {
            $logContext['context'] = $e->getContext();
        }
        
        LoggerFactory::error("Exception occurred", $logContext);
    }

    /**
     * 检查是否为严重异常
     */
    private function isCriticalException(\Throwable $e): bool
    {
        $criticalExceptions = [
            'OutOfMemoryException',
            'RuntimeException',
            'LogicException'
        ];
        
        return in_array(get_class($e), $criticalExceptions) || 
               $e->getCode() >= 500;
    }

    /**
     * 获取异常统计信息
     */
    public function getExceptionStats(): array
    {
        return $this->exceptionStats;
    }

    /**
     * 清理过期统计信息
     */
    public function cleanupStats(): void
    {
        $currentTime = time();
        $oneHourAgo = $currentTime - 3600;
        
        // 清理过期的最近异常记录
        $this->exceptionStats['recent'] = array_filter(
            $this->exceptionStats['recent'],
            fn($exception) => $exception['timestamp'] > $oneHourAgo
        );
        
        // 清理玩家统计（保留最近24小时的）
        $oneDayAgo = $currentTime - 86400;
        // 这里需要更复杂的实现来跟踪时间戳
    }
}