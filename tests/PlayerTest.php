<?php

namespace PfinalClub\AsyncioGamekit\Tests;

use PHPUnit\Framework\TestCase;
use PfinalClub\AsyncioGamekit\Player;

class PlayerTest extends TestCase
{
    private Player $player;

    protected function setUp(): void
    {
        $this->player = new Player('test_player_001', null, 'TestPlayer');
    }

    public function testGetId(): void
    {
        $this->assertEquals('test_player_001', $this->player->getId());
    }

    public function testGetName(): void
    {
        $this->assertEquals('TestPlayer', $this->player->getName());
    }

    public function testSetName(): void
    {
        $this->player->setName('NewName');
        $this->assertEquals('NewName', $this->player->getName());
    }

    public function testSetAndGetData(): void
    {
        $this->player->set('score', 100);
        $this->assertEquals(100, $this->player->get('score'));
    }

    public function testGetDataWithDefault(): void
    {
        $this->assertEquals('default', $this->player->get('nonexistent', 'default'));
    }

    public function testHasData(): void
    {
        $this->assertFalse($this->player->has('score'));
        $this->player->set('score', 100);
        $this->assertTrue($this->player->has('score'));
    }

    public function testReadyState(): void
    {
        $this->assertFalse($this->player->isReady());
        $this->player->setReady(true);
        $this->assertTrue($this->player->isReady());
    }

    public function testToArray(): void
    {
        $this->player->set('level', 5);
        $this->player->setReady(true);

        $array = $this->player->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('test_player_001', $array['id']);
        $this->assertEquals('TestPlayer', $array['name']);
        $this->assertTrue($array['ready']);
        $this->assertEquals(5, $array['data']['level']);
    }

    public function testDefaultNameGeneration(): void
    {
        $player = new Player('player_123', null);
        $this->assertEquals('Player_player_123', $player->getName());
    }

    public function testGetAllData(): void
    {
        $this->player->set('score', 100);
        $this->player->set('level', 5);

        $data = $this->player->getData();
        $this->assertIsArray($data);
        $this->assertEquals(100, $data['score']);
        $this->assertEquals(5, $data['level']);
    }
}

