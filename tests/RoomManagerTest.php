<?php

namespace PfinalClub\AsyncioGamekit\Tests;

use PHPUnit\Framework\TestCase;
use PfinalClub\AsyncioGamekit\RoomManager;
use PfinalClub\AsyncioGamekit\Room;
use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\Tests\Helpers\TestRoom;
use Generator;

class RoomManagerTest extends TestCase
{
    private RoomManager $manager;

    protected function setUp(): void
    {
        $this->manager = new RoomManager();
    }

    public function testCreateRoom(): void
    {
        $room = $this->manager->createRoom(TestRoom::class, 'room_001');

        $this->assertInstanceOf(Room::class, $room);
        $this->assertEquals('room_001', $room->getId());
    }

    public function testCreateRoomWithAutoId(): void
    {
        $room = $this->manager->createRoom(TestRoom::class);
        
        $this->assertNotEmpty($room->getId());
        $this->assertStringStartsWith('room_', $room->getId());
    }

    public function testCreateRoomWithConfig(): void
    {
        $config = ['max_players' => 8, 'min_players' => 4];
        $room = $this->manager->createRoom(TestRoom::class, 'room_001', $config);

        $array = $room->toArray();
        $this->assertEquals(8, $array['config']['max_players']);
        $this->assertEquals(4, $array['config']['min_players']);
    }

    public function testCreateDuplicateRoom(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Room room_001 already exists');

        $this->manager->createRoom(TestRoom::class, 'room_001');
        $this->manager->createRoom(TestRoom::class, 'room_001');
    }

    public function testCreateRoomWithInvalidClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->manager->createRoom(\stdClass::class, 'room_001');
    }

    public function testGetRoom(): void
    {
        $room = $this->manager->createRoom(TestRoom::class, 'room_001');
        $retrieved = $this->manager->getRoom('room_001');

        $this->assertSame($room, $retrieved);
    }

    public function testGetNonexistentRoom(): void
    {
        $room = $this->manager->getRoom('nonexistent');
        $this->assertNull($room);
    }

    public function testGetRooms(): void
    {
        $room1 = $this->manager->createRoom(TestRoom::class, 'room_001');
        $room2 = $this->manager->createRoom(TestRoom::class, 'room_002');

        $rooms = $this->manager->getRooms();

        $this->assertCount(2, $rooms);
        $this->assertArrayHasKey('room_001', $rooms);
        $this->assertArrayHasKey('room_002', $rooms);
    }

    public function testJoinRoom(): void
    {
        $room = $this->manager->createRoom(TestRoom::class, 'room_001');
        $player = new Player('p1', null, 'Player1');

        $result = $this->manager->joinRoom($player, 'room_001');

        $this->assertTrue($result);
        $this->assertEquals(1, $room->getPlayerCount());
        $this->assertSame($room, $this->manager->getPlayerRoom($player));
    }

    public function testJoinNonexistentRoom(): void
    {
        $player = new Player('p1', null, 'Player1');
        
        // 现在应该抛出异常而不是返回false
        $this->expectException(\PfinalClub\AsyncioGamekit\Exceptions\RoomException::class);
        $this->expectExceptionMessage('Room nonexistent not found');
        $this->manager->joinRoom($player, 'nonexistent');
    }

    public function testLeaveRoom(): void
    {
        $room = $this->manager->createRoom(TestRoom::class, 'room_001');
        $player = new Player('p1', null, 'Player1');

        $this->manager->joinRoom($player, 'room_001');
        $result = $this->manager->leaveRoom($player);

        $this->assertTrue($result);
        $this->assertEquals(0, $room->getPlayerCount());
        $this->assertNull($this->manager->getPlayerRoom($player));
    }

    public function testLeaveRoomWhenNotInRoom(): void
    {
        $player = new Player('p1', null, 'Player1');
        $result = $this->manager->leaveRoom($player);

        $this->assertFalse($result);
    }

    public function testGetPlayerRoom(): void
    {
        $room = $this->manager->createRoom(TestRoom::class, 'room_001');
        $player = new Player('p1', null, 'Player1');

        $this->assertNull($this->manager->getPlayerRoom($player));

        $this->manager->joinRoom($player, 'room_001');
        $this->assertSame($room, $this->manager->getPlayerRoom($player));
    }

    public function testQuickMatch(): void
    {
        $player = new Player('p1', null, 'Player1');
        $room = $this->manager->quickMatch($player, TestRoom::class);

        $this->assertInstanceOf(Room::class, $room);
        $this->assertEquals(1, $room->getPlayerCount());
        $this->assertSame($room, $this->manager->getPlayerRoom($player));
    }

    public function testQuickMatchJoinsExistingRoom(): void
    {
        $player1 = new Player('p1', null, 'Player1');
        $player2 = new Player('p2', null, 'Player2');

        $room1 = $this->manager->quickMatch($player1, TestRoom::class);
        $room2 = $this->manager->quickMatch($player2, TestRoom::class);

        // 应该加入同一个房间（通过房间ID判断，而不是对象引用）
        $this->assertEquals($room1->getId(), $room2->getId());
        $this->assertEquals(2, $room1->getPlayerCount());
    }

    public function testGetStats(): void
    {
        $this->manager->createRoom(TestRoom::class, 'room_001');
        $this->manager->createRoom(TestRoom::class, 'room_002');

        $player = new Player('p1', null, 'Player1');
        $this->manager->joinRoom($player, 'room_001');

        $stats = $this->manager->getStats();

        $this->assertEquals(2, $stats['total_rooms']);
        $this->assertEquals(1, $stats['total_players']);
        $this->assertArrayHasKey('rooms_by_status', $stats);
    }
}

