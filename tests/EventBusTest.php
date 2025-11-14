<?php

namespace PfinalClub\AsyncioGamekit\Tests;

use PHPUnit\Framework\TestCase;
use PfinalClub\AsyncioGamekit\Event\{Event, EventBus, EventListenerInterface};

class EventBusTest extends TestCase
{
    private EventBus $eventBus;

    protected function setUp(): void
    {
        $this->eventBus = new EventBus();
    }

    public function testSubscribeWithCallable(): void
    {
        $called = false;
        
        $listenerId = $this->eventBus->subscribe('test.event', function(Event $event) use (&$called) {
            $called = true;
        });
        
        $this->assertIsString($listenerId);
        $this->assertNotEmpty($this->eventBus->getListeners('test.event'));
    }

    public function testSubscribeWithListener(): void
    {
        $listener = new class implements EventListenerInterface {
            public bool $called = false;
            
            public function handle(Event $event): void
            {
                $this->called = true;
            }
        };
        
        $listenerId = $this->eventBus->subscribe('test.event', $listener);
        
        $this->assertIsString($listenerId);
    }

    public function testPublishEvent(): void
    {
        $receivedData = null;
        
        $this->eventBus->subscribe('test.event', function(Event $event) use (&$receivedData) {
            $receivedData = $event->getData();
        });
        
        $this->eventBus->publish('test.event', ['key' => 'value']);
        
        $this->assertEquals(['key' => 'value'], $receivedData);
    }

    public function testPublishEventObject(): void
    {
        $receivedEvent = null;
        
        $this->eventBus->subscribe('test.event', function(Event $event) use (&$receivedEvent) {
            $receivedEvent = $event;
        });
        
        $originalEvent = new Event('test.event', ['data' => 'test']);
        $returnedEvent = $this->eventBus->publish($originalEvent);
        
        $this->assertSame($originalEvent, $returnedEvent);
        $this->assertSame($originalEvent, $receivedEvent);
    }

    public function testMultipleListeners(): void
    {
        $count = 0;
        
        $this->eventBus->subscribe('test.event', function() use (&$count) {
            $count++;
        });
        
        $this->eventBus->subscribe('test.event', function() use (&$count) {
            $count++;
        });
        
        $this->eventBus->subscribe('test.event', function() use (&$count) {
            $count++;
        });
        
        $this->eventBus->publish('test.event');
        
        $this->assertEquals(3, $count);
    }

    public function testPriorityOrdering(): void
    {
        $execution = [];
        
        $this->eventBus->subscribe('test.event', function() use (&$execution) {
            $execution[] = 'low';
        }, 0);
        
        $this->eventBus->subscribe('test.event', function() use (&$execution) {
            $execution[] = 'high';
        }, 10);
        
        $this->eventBus->subscribe('test.event', function() use (&$execution) {
            $execution[] = 'medium';
        }, 5);
        
        $this->eventBus->publish('test.event');
        
        $this->assertEquals(['high', 'medium', 'low'], $execution);
    }

    public function testUnsubscribe(): void
    {
        $called = false;
        
        $listenerId = $this->eventBus->subscribe('test.event', function() use (&$called) {
            $called = true;
        });
        
        $result = $this->eventBus->unsubscribe($listenerId);
        
        $this->assertTrue($result);
        
        $this->eventBus->publish('test.event');
        
        $this->assertFalse($called);
    }

    public function testUnsubscribeNonexistentListener(): void
    {
        $result = $this->eventBus->unsubscribe('nonexistent_listener_id');
        
        $this->assertFalse($result);
    }

    public function testStopPropagation(): void
    {
        $count = 0;
        
        $this->eventBus->subscribe('test.event', function(Event $event) use (&$count) {
            $count++;
            $event->stopPropagation();
        }, 10);
        
        $this->eventBus->subscribe('test.event', function(Event $event) use (&$count) {
            $count++;
        }, 5);
        
        $this->eventBus->publish('test.event');
        
        // 只有第一个监听器被调用
        $this->assertEquals(1, $count);
    }

    public function testClearEventListeners(): void
    {
        $this->eventBus->subscribe('test.event', function() {});
        $this->eventBus->subscribe('test.event', function() {});
        
        $this->assertNotEmpty($this->eventBus->getListeners('test.event'));
        
        $this->eventBus->clear('test.event');
        
        $this->assertEmpty($this->eventBus->getListeners('test.event'));
    }

    public function testClearAll(): void
    {
        $this->eventBus->subscribe('event1', function() {});
        $this->eventBus->subscribe('event2', function() {});
        
        $this->eventBus->clearAll();
        
        $stats = $this->eventBus->getStats();
        $this->assertEquals(0, $stats['total_events']);
        $this->assertEquals(0, $stats['total_listeners']);
    }

    public function testGetStats(): void
    {
        $this->eventBus->subscribe('event1', function() {});
        $this->eventBus->subscribe('event1', function() {});
        $this->eventBus->subscribe('event2', function() {});
        
        $stats = $this->eventBus->getStats();
        
        $this->assertEquals(2, $stats['total_events']);
        $this->assertEquals(3, $stats['total_listeners']);
        $this->assertEquals(2, $stats['events']['event1']);
        $this->assertEquals(1, $stats['events']['event2']);
    }

    public function testListenerException(): void
    {
        $called = false;
        
        // 第一个监听器抛出异常
        $this->eventBus->subscribe('test.event', function() {
            throw new \RuntimeException('Test error');
        }, 10);
        
        // 第二个监听器应该仍然被调用
        $this->eventBus->subscribe('test.event', function() use (&$called) {
            $called = true;
        }, 5);
        
        // 发布事件不应该抛出异常
        $this->eventBus->publish('test.event');
        
        // 第二个监听器应该被调用
        $this->assertTrue($called);
    }

    public function testEventTimestamp(): void
    {
        $event = new Event('test.event', ['data' => 'value']);
        
        $this->assertIsFloat($event->getTimestamp());
        $this->assertGreaterThan(0, $event->getTimestamp());
    }

    public function testEventGetters(): void
    {
        $event = new Event('test.event', ['key' => 'value']);
        
        $this->assertEquals('test.event', $event->getName());
        $this->assertEquals(['key' => 'value'], $event->getData());
        $this->assertFalse($event->isPropagationStopped());
        
        $event->stopPropagation();
        
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testGetListenersForNonexistentEvent(): void
    {
        $listeners = $this->eventBus->getListeners('nonexistent.event');
        
        $this->assertEmpty($listeners);
    }

    public function testPublishWithoutListeners(): void
    {
        // 应该不抛出异常
        $event = $this->eventBus->publish('nonexistent.event', ['data' => 'value']);
        
        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals('nonexistent.event', $event->getName());
    }
}

