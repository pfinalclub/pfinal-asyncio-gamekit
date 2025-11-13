<?php

namespace PfinalClub\AsyncioGamekit\Middleware;

use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\Exceptions\ServerException;

/**
 * 验证中间件
 * 验证消息格式和数据有效性
 */
class ValidationMiddleware implements MiddlewareInterface
{
    /** @var array<string, array> 事件验证规则 */
    private array $rules = [];

    /**
     * 添加验证规则
     * 
     * @param string $event 事件名称
     * @param array $rules 验证规则
     */
    public function addRule(string $event, array $rules): self
    {
        $this->rules[$event] = $rules;
        return $this;
    }

    public function handle(Player $player, string $event, mixed $data, callable $next): mixed
    {
        // 如果有该事件的验证规则，执行验证
        if (isset($this->rules[$event])) {
            $this->validate($event, $data);
        }

        return $next($player, $event, $data);
    }

    /**
     * 验证数据
     * 
     * @throws ServerException
     */
    private function validate(string $event, mixed $data): void
    {
        $rules = $this->rules[$event];

        if (!is_array($data)) {
            throw ServerException::invalidMessageFormat("Expected array data for event: $event");
        }

        foreach ($rules as $field => $rule) {
            // 检查必需字段
            if (isset($rule['required']) && $rule['required'] && !isset($data[$field])) {
                throw ServerException::invalidMessageFormat("Missing required field: $field");
            }

            // 检查类型
            if (isset($data[$field]) && isset($rule['type'])) {
                $actualType = gettype($data[$field]);
                $expectedType = $rule['type'];

                if ($actualType !== $expectedType) {
                    throw ServerException::invalidMessageFormat(
                        "Invalid type for field $field: expected $expectedType, got $actualType"
                    );
                }
            }

            // 检查长度（字符串）
            if (isset($data[$field]) && is_string($data[$field]) && isset($rule['max_length'])) {
                if (mb_strlen($data[$field]) > $rule['max_length']) {
                    throw ServerException::invalidMessageFormat(
                        "Field $field exceeds max length of {$rule['max_length']}"
                    );
                }
            }

            // 检查范围（数字）
            if (isset($data[$field]) && is_numeric($data[$field])) {
                if (isset($rule['min']) && $data[$field] < $rule['min']) {
                    throw ServerException::invalidMessageFormat(
                        "Field $field is below minimum value of {$rule['min']}"
                    );
                }
                if (isset($rule['max']) && $data[$field] > $rule['max']) {
                    throw ServerException::invalidMessageFormat(
                        "Field $field exceeds maximum value of {$rule['max']}"
                    );
                }
            }
        }
    }
}

