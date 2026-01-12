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

    /**
     * 编码游戏消息
     * 
     * @param string $event 事件名称
     * @param mixed $data 数据
     * @return string JSON 字符串
     * @throws \JsonException
     */
    public static function encodeMessage(string $event, mixed $data = null): string
    {
        return self::encode([
            'event' => $event,
            'data' => $data,
            'timestamp' => microtime(true)
        ]);
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
