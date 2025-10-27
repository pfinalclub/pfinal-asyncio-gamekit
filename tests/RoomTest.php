<?php

namespace PfinalClub\AsyncioGamekit\Tests;

use PHPUnit\Framework\TestCase;
use PfinalClub\AsyncioGamekit\Room;
use PfinalClub\AsyncioGamekit\Player;
use PfinalClub\AsyncioGamekit\Tests\Helpers\TestRoom;

class RoomTest extends TestCase
{
    private TestRoom $room;

    protected function setUp(): void
    {
        $this->room = new TestRoom('test_room_001');
    }

    public function testGetId(): void
    {
        $this->assertEquals('test_room_001', $this->room->getId());
    }

    public function testGetStatus(): void
    {
        $this->assertEquals('waiting', $this->room->getStatus());
    }

    public function testAddPlayer(): void
    {
        $player = new Player('p1', null, 'Player1');
        $result = $this->room->addPlayer($player);

        $this->assertTrue($result);
        $this->assertEquals(1, $this->room->getPlayerCount());
        $this->assertSame($this->room, $player->getRoom());
    }

    public function testAddPlayerMaxLimit(): void
    {
        $room = new TestRoom('room_001', ['max_players' => 2]);

        $player1 = new Player('p1', null, 'Player1');
        $player2 = new Player('p2', null, 'Player2');
        $player3 = new Player('p3', null, 'Player3');

        $this->assertTrue($room->addPlayer($player1));
        $this->assertTrue($room->addPlayer($player2));
        
        // 现在应该抛出异常而不是返回false
        $this->expectException(\PfinalClub\AsyncioGamekit\Exceptions\RoomException::class);
        $this->expectExceptionMessage('Room room_001 is full');
        $room->addPlayer($player3);
    }

    public function testAddSamePlayerTwice(): void
    {
        $player = new Player('p1', null, 'Player1');
        
        $this->assertTrue($this->room->addPlayer($player));
        
        // 现在应该抛出异常而不是返回false
        $this->expectException(\PfinalClub\AsyncioGamekit\Exceptions\RoomException::class);
        $this->expectExceptionMessage('Player p1 is already in room');
        $this->room->addPlayer($player);
    }

    public function testRemovePlayer(): void
    {
        $player = new Player('p1', null, 'Player1');
        $this->room->addPlayer($player);

        $result = $this->room->removePlayer('p1');

        $this->assertTrue($result);
        $this->assertEquals(0, $this->room->getPlayerCount());
        $this->assertNull($player->getRoom());
    }

    public function testRemoveNonexistentPlayer(): void
    {
        $result = $this->room->removePlayer('nonexistent');
        $this->assertFalse($result);
    }

    public function testGetPlayer(): void
    {
        $player = new Player('p1', null, 'Player1');
        $this->room->addPlayer($player);

        $retrieved = $this->room->getPlayer('p1');
        $this->assertSame($player, $retrieved);

        $nonexistent = $this->room->getPlayer('p2');
        $this->assertNull($nonexistent);
    }

    public function testGetPlayers(): void
    {
        $player1 = new Player('p1', null, 'Player1');
        $player2 = new Player('p2', null, 'Player2');

        $this->room->addPlayer($player1);
        $this->room->addPlayer($player2);

        $players = $this->room->getPlayers();
        $this->assertCount(2, $players);
        $this->assertArrayHasKey('p1', $players);
        $this->assertArrayHasKey('p2', $players);
    }

    public function testCanStart(): void
    {
        $room = new TestRoom('room_001', ['min_players' => 2, 'max_players' => 4]);

        $player1 = new Player('p1', null, 'Player1');
        $player2 = new Player('p2', null, 'Player2');

        $this->assertFalse($room->canStart()); // 人数不够

        $room->addPlayer($player1);
        $this->assertFalse($room->canStart()); // 还差1人

        $room->addPlayer($player2);
        $this->assertTrue($room->canStart()); // 可以开始
    }

    public function testSetAndGetData(): void
    {
        $this->room->set('round', 3);
        $this->assertEquals(3, $this->room->get('round'));
    }

    public function testGetDataWithDefault(): void
    {
        $this->assertEquals('default', $this->room->get('nonexistent', 'default'));
    }

    public function testToArray(): void
    {
        $player = new Player('p1', null, 'Player1');
        $this->room->addPlayer($player);
        $this->room->set('custom_data', 'value');

        $array = $this->room->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('test_room_001', $array['id']);
        $this->assertEquals('waiting', $array['status']);
        $this->assertEquals(1, $array['player_count']);
        $this->assertIsArray($array['players']);
        $this->assertArrayHasKey('custom_data', $array['data']);
    }
}

