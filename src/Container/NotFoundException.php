<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit\Container;

/**
 * 服务未找到异常
 */
class NotFoundException extends ContainerException
{
    public static function forService(string $id): self
    {
        return new self("Service '{$id}' not found in container");
    }
}

