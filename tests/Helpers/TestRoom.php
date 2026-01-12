<?php

namespace PfinalClub\AsyncioGamekit\Tests\Helpers;

use PfinalClub\AsyncioGamekit\Room\Room;

/**
 * 测试用的空房间实现
 */
class TestRoom extends Room
{
    /**
     * 游戏主循环 - 空实现用于测试
     */
    protected function run(): mixed
    {
        // 空实现用于测试
        return null;
    }
}

