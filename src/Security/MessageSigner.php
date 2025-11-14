<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Security;

/**
 * 消息签名器
 * 
 * 使用 HMAC-SHA256 对 WebSocket 消息进行签名和验证
 * 防止消息被篡改和伪造
 */
class MessageSigner
{
    private string $secretKey;
    private string $algorithm = 'sha256';

    /**
     * @param string $secretKey 密钥（建议使用环境变量）
     */
    public function __construct(string $secretKey)
    {
        if (strlen($secretKey) < 32) {
            throw new \InvalidArgumentException('Secret key must be at least 32 characters long');
        }
        
        $this->secretKey = $secretKey;
    }

    /**
     * 对数据进行签名
     * 
     * @param array $data 要签名的数据
     * @return string 签名字符串
     */
    public function sign(array $data): string
    {
        $payload = json_encode($data);
        return hash_hmac($this->algorithm, $payload, $this->secretKey);
    }

    /**
     * 验证签名
     * 
     * @param array $data 数据
     * @param string $signature 签名
     * @return bool 签名是否有效
     */
    public function verify(array $data, string $signature): bool
    {
        $expectedSignature = $this->sign($data);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * 对消息进行签名（包含时间戳）
     * 
     * @param string $event 事件名称
     * @param mixed $data 数据
     * @return array 包含签名的消息
     */
    public function signMessage(string $event, mixed $data): array
    {
        $message = [
            'event' => $event,
            'data' => $data,
            'timestamp' => microtime(true),
        ];
        
        $message['signature'] = $this->sign($message);
        
        return $message;
    }

    /**
     * 验证消息签名
     * 
     * @param array $message 消息
     * @param float $maxAge 最大时效（秒），0表示不检查
     * @return bool 消息是否有效
     */
    public function verifyMessage(array $message, float $maxAge = 300): bool
    {
        // 检查必需字段
        if (!isset($message['signature'])) {
            return false;
        }
        
        $signature = $message['signature'];
        unset($message['signature']);
        
        // 验证签名
        if (!$this->verify($message, $signature)) {
            return false;
        }
        
        // 检查时效性（可选）
        if ($maxAge > 0 && isset($message['timestamp'])) {
            $age = microtime(true) - $message['timestamp'];
            if ($age > $maxAge) {
                return false; // 消息过期
            }
        }
        
        return true;
    }

    /**
     * 设置签名算法
     * 
     * @param string $algorithm 算法名称（sha256, sha384, sha512等）
     */
    public function setAlgorithm(string $algorithm): void
    {
        if (!in_array($algorithm, hash_algos(), true)) {
            throw new \InvalidArgumentException("Unsupported algorithm: {$algorithm}");
        }
        
        $this->algorithm = $algorithm;
    }

    /**
     * 获取当前算法
     * 
     * @return string
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }
}

