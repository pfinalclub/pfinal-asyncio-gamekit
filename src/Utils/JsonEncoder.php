<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Utils;

/**
 * JSON 编码工具类
 * 统一 JSON 编码逻辑，便于后续优化（如使用 igbinary）
 */
class JsonEncoder
{
    /** JSON 编码标志 */
    private const ENCODE_FLAGS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    /**
     * 编码数组为 JSON 字符串
     * 
     * @param array $data 要编码的数据
     * @return string JSON 字符串
     * @throws \JsonException
     */
    public static function encode(array $data): string
    {
        return json_encode($data, self::ENCODE_FLAGS);
    }

    /** @var float 缓存的当前时间戳（毫秒级精度） */
    private static float $cachedTimestamp = 0;
    
    /** @var int 缓存的当前时间戳（毫秒） */
    private static int $cachedTimestampMs = 0;

    /**
     * 获取缓存的当前时间戳（每毫秒更新一次）
     * 减少 microtime() 调用次数，提升高频场景性能
     */
    private static function getCachedTimestamp(): float
    {
        $nowMs = (int)(microtime(true) * 1000);
        if ($nowMs !== self::$cachedTimestampMs) {
            self::$cachedTimestampMs = $nowMs;
            self::$cachedTimestamp = microtime(true);
        }
        return self::$cachedTimestamp;
    }

    /**
     * 编码游戏消息
     * 
     * @param string $event 事件名称
     * @param mixed $data 数据
     * @param bool $includeTimestamp 是否包含时间戳（默认 true）
     * @return string JSON 字符串
     * @throws \JsonException
     */
    public static function encodeMessage(string $event, mixed $data = null, bool $includeTimestamp = true): string
    {
        $message = [
            'event' => $event,
            'data' => $data,
        ];
        
        if ($includeTimestamp) {
            $message['timestamp'] = self::getCachedTimestamp();
        }
        
        return self::encode($message);
    }

    /**
     * 解码 JSON 字符串
     * 
     * @param string $json JSON 字符串
     * @param int $maxDepth 最大嵌套深度
     * @return array 解码后的数组
     * @throws \JsonException
     */
    public static function decode(string $json, int $maxDepth = 512): array
    {
        return json_decode($json, true, $maxDepth, JSON_THROW_ON_ERROR);
    }
}
