<?php

declare(strict_types=1);
namespace PfinalClub\AsyncioGamekit;

/**
 * Room 房间基类（兼容层）
 * 
 * 在 v3.0 中，Room 类已被重构为模块化结构。
 * 此文件作为兼容层，继承新的 Room\Room 类以保持向后兼容。
     * 
 * @deprecated 建议直接使用 PfinalClub\AsyncioGamekit\Room\Room
 */
abstract class Room extends \PfinalClub\AsyncioGamekit\Room\Room
{
    // 完全继承新的 Room 类，无需额外代码
}
