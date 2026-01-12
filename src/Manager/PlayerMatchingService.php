<?php

declare(strict_types=1);

namespace PfinalClub\AsyncioGamekit\Manager;

use PfinalClub\AsyncioGamekit\Room\Room;
use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\Constants\RoomStatus;

/**
 * 玩家匹配服务
 * 
 * 负责玩家的房间匹配逻辑
 */
class PlayerMatchingService
{
    private RoomRegistry $registry;
    private RoomFactory $factory;

    public function __construct(RoomRegistry $registry, RoomFactory $factory)
    {
        $this->registry = $registry;
        $this->factory = $factory;
    }

    /**
     * 查找可用房间（有空位且未开始）
     * 
     * @param string $roomClass 房间类名
     * @return Room|null
     */
    public function findAvailableRoom(string $roomClass): ?Room
    {
        // 使用索引快速查找 waiting 状态的房间
        $waitingRooms = $this->registry->findByClassAndStatus($roomClass, RoomStatus::WAITING);
        
        foreach ($waitingRooms as $room) {
            if ($room->getPlayerCount() < $room->getMaxPlayers()) {
                return $room;
            }
        }
        
        return null;
    }

    /**
     * 快速匹配（自动创建或加入房间）
     * 
     * @param string $roomClass 房间类名
     * @param array $config 房间配置
     * @return Room
     */
    public function quickMatch(string $roomClass, array $config = []): Room
    {
        // 先尝试找到可用房间
        $room = $this->findAvailableRoom($roomClass);
        
        // 没有可用房间则创建新房间
        if (!$room) {
            $roomId = uniqid('room_', true);
            $room = $this->factory->create($roomClass, $roomId, $config);
            $this->registry->register($room);
        }

        return $room;
    }

    /**
     * 基于技能的匹配（可扩展）
     * 
     * @param Player $player
     * @param string $roomClass
     * @param int $skillLevel
     * @return Room|null
     */
    public function findBySkillLevel(Player $player, string $roomClass, int $skillLevel): ?Room
    {
        // 查找等待中的房间
        $waitingRooms = $this->registry->findByClassAndStatus($roomClass, RoomStatus::WAITING);
        
        $bestMatch = null;
        $minSkillDiff = PHP_INT_MAX;
        
        foreach ($waitingRooms as $room) {
            // 房间已满，跳过
            if ($room->getPlayerCount() >= $room->getMaxPlayers()) {
                continue;
            }
            
            // 计算房间平均技能等级（简化实现）
            $roomSkillLevel = (int)$room->get('skill_level', 0);
            $skillDiff = abs($roomSkillLevel - $skillLevel);
            
            if ($skillDiff < $minSkillDiff) {
                $minSkillDiff = $skillDiff;
                $bestMatch = $room;
            }
        }
        
        return $bestMatch;
    }

    /**
     * 获取匹配统计信息
     * 
     * @return array
     */
    public function getMatchingStats(): array
    {
        return [
            'available_rooms' => count($this->registry->findByClassAndStatus('*', RoomStatus::WAITING)),
            'total_rooms' => count($this->registry->getAll()),
        ];
    }
}

