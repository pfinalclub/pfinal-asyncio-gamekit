<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Security;

use PfinalClub\AsyncioGamekit\Exceptions\ServerException;
use PfinalClub\AsyncioGamekit\Constants\GameEvents;
use PfinalClub\AsyncioGamekit\Utils\JsonEncoder;

/**
 * 输入验证器
 * 
 * 验证和清理用户输入，防止注入攻击
 */
class InputValidator
{
    /** 允许的事件白名单（使用 GameEvents 常量初始化） */
    private static ?array $allowedEvents = null;

    /** 最大消息大小 (64KB) */
    private const MAX_MESSAGE_SIZE = 65536;

    /** 最大嵌套层级 */
    private const MAX_NEST_LEVEL = 5;

    /** 玩家名称最大长度 */
    private const MAX_PLAYER_NAME_LENGTH = 32;

    /**
     * 初始化允许的事件列表
     */
    private static function initAllowedEvents(): void
    {
        if (self::$allowedEvents === null) {
            self::$allowedEvents = GameEvents::getAllowedClientEvents();
        }
    }

    /**
     * 验证消息格式
     * 
     * @param string $data 原始消息数据
     * @return array 解析后的消息
     * @throws ServerException
     */
    public function validateMessage(string $data): array
    {
        self::initAllowedEvents();
        // 1. 大小检查
        if (strlen($data) > self::MAX_MESSAGE_SIZE) {
            throw ServerException::invalidMessageFormat("Message too large");
        }

        // 2. JSON 格式检查
        try {
            $message = \PfinalClub\AsyncioGamekit\Utils\JsonEncoder::decode($data, self::MAX_NEST_LEVEL);
        } catch (\JsonException $e) {
            throw ServerException::invalidMessageFormat("Invalid JSON format: " . $e->getMessage());
        }

        if (!is_array($message)) {
            throw ServerException::invalidMessageFormat("Expected JSON object");
        }

        // 3. 必需字段检查
        if (!isset($message['event']) || !is_string($message['event'])) {
            throw ServerException::invalidMessageFormat("Missing or invalid 'event' field");
        }

        // 4. 事件白名单检查
        if (!in_array($message['event'], self::$allowedEvents, true)) {
            throw ServerException::invalidMessageFormat("Unknown event type: {$message['event']}");
        }

        // 5. 数据字段类型检查
        if (isset($message['data']) && !is_array($message['data']) && !is_null($message['data'])) {
            throw ServerException::invalidMessageFormat("Invalid 'data' field type");
        }

        return $message;
    }

    /**
     * 清理玩家名称
     * 
     * @param string $name 原始名称
     * @return string 清理后的名称
     */
    public function sanitizePlayerName(string $name): string
    {
        // 1. 去除HTML标签
        $name = strip_tags($name);
        
        // 2. 转义特殊字符
        $name = htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // 3. 限制长度
        $name = mb_substr($name, 0, self::MAX_PLAYER_NAME_LENGTH);
        
        // 4. 去除控制字符
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name);
        
        // 5. 去除多余空格
        $name = preg_replace('/\s+/', ' ', $name);
        $name = trim($name);
        
        // 6. 如果为空，返回默认名称
        return $name !== '' ? $name : 'Anonymous';
    }

    /**
     * 验证和清理房间配置
     * 
     * @param array $config 原始配置
     * @return array 清理后的配置
     */
    public function sanitizeRoomConfig(array $config): array
    {
        $sanitized = [];
        
        // 整型字段
        $intFields = [
            'max_players' => ['min' => 2, 'max' => 100],
            'min_players' => ['min' => 1, 'max' => 100],
        ];
        
        foreach ($intFields as $field => $limits) {
            if (isset($config[$field])) {
                $value = (int)$config[$field];
                $sanitized[$field] = max($limits['min'], min($limits['max'], $value));
            }
        }
        
        // 布尔字段
        $boolFields = ['auto_start'];
        foreach ($boolFields as $field) {
            if (isset($config[$field])) {
                $sanitized[$field] = (bool)$config[$field];
            }
        }
        
        // 确保 min_players <= max_players
        if (isset($sanitized['min_players']) && isset($sanitized['max_players'])) {
            $sanitized['min_players'] = min($sanitized['min_players'], $sanitized['max_players']);
        }
        
        return $sanitized;
    }

    /**
     * 验证房间ID格式
     * 
     * @param string $roomId 房间ID
     * @return bool 是否有效
     */
    public function validateRoomId(string $roomId): bool
    {
        // 只允许字母、数字、下划线、连字符
        return (bool)preg_match('/^[a-zA-Z0-9_-]+$/', $roomId) && strlen($roomId) <= 64;
    }

    /**
     * 验证房间类名
     * 
     * @param string $className 类名
     * @param array $allowedClasses 允许的类名列表
     * @return bool 是否有效
     */
    public function validateRoomClass(string $className, array $allowedClasses = []): bool
    {
        // 如果有白名单，使用白名单验证
        if (!empty($allowedClasses)) {
            return in_array($className, $allowedClasses, true);
        }
        
        // 否则检查类是否存在
        return class_exists($className);
    }

    /**
     * 过滤数组中的敏感字段
     * 
     * @param array $data 数据
     * @param array $sensitiveFields 敏感字段列表
     * @return array 过滤后的数据
     */
    public function filterSensitiveFields(array $data, array $sensitiveFields = ['password', 'token', 'secret']): array
    {
        foreach ($sensitiveFields as $field) {
            unset($data[$field]);
        }
        
        return $data;
    }

    /**
     * 添加自定义允许的事件
     * 
     * @param string $event 事件名称
     */
    public static function addAllowedEvent(string $event): void
    {
        self::initAllowedEvents();
        
        if (!in_array($event, self::$allowedEvents, true)) {
            self::$allowedEvents[] = $event;
        }
    }

    /**
     * 获取允许的事件列表
     * 
     * @return array
     */
    public static function getAllowedEvents(): array
    {
        self::initAllowedEvents();
        
        return self::$allowedEvents;
    }
}

