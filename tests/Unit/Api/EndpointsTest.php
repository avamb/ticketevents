<?php

namespace Bil24\Tests\Unit\Api;

use Bil24\Api\Client;
use Bil24\Api\Endpoints;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for API Endpoints
 */
class EndpointsTest extends TestCase {

    private Endpoints $endpoints;
    private Client|MockObject $client_mock;

    protected function setUp(): void {
        parent::setUp();
        
        $this->client_mock = $this->createMock(Client::class);
        $this->endpoints = new Endpoints($this->client_mock);
    }

    public function test_endpoints_initialization(): void {
        $this->assertInstanceOf(Endpoints::class, $this->endpoints);
    }

    public function test_endpoints_initialization_without_client(): void {
        $endpoints = new Endpoints();
        $this->assertInstanceOf(Endpoints::class, $endpoints);
    }

    public function test_get_events(): void {
        $expected_response = [
            ['id' => 1, 'name' => 'Event 1'],
            ['id' => 2, 'name' => 'Event 2']
        ];

        $this->client_mock
            ->expects($this->once())
            ->method('get')
            ->with('events', ['limit' => 10, 'offset' => 0])
            ->willReturn($expected_response);

        $result = $this->endpoints->get_events(['limit' => 10, 'offset' => 0]);
        $this->assertEquals($expected_response, $result);
    }

    public function test_get_event(): void {
        $event_id = 123;
        $expected_response = ['id' => $event_id, 'name' => 'Test Event'];

        $this->client_mock
            ->expects($this->once())
            ->method('get')
            ->with("events/{$event_id}")
            ->willReturn($expected_response);

        $result = $this->endpoints->get_event($event_id);
        $this->assertEquals($expected_response, $result);
    }

    public function test_create_event(): void {
        $event_data = ['name' => 'New Event', 'description' => 'Test description'];
        $expected_response = ['id' => 456] + $event_data;

        $this->client_mock
            ->expects($this->once())
            ->method('post')
            ->with('events', $event_data)
            ->willReturn($expected_response);

        $result = $this->endpoints->create_event($event_data);
        $this->assertEquals($expected_response, $result);
    }

    public function test_update_event(): void {
        $event_id = 123;
        $event_data = ['name' => 'Updated Event'];
        $expected_response = ['id' => $event_id] + $event_data;

        $this->client_mock
            ->expects($this->once())
            ->method('put')
            ->with("events/{$event_id}", $event_data)
            ->willReturn($expected_response);

        $result = $this->endpoints->update_event($event_id, $event_data);
        $this->assertEquals($expected_response, $result);
    }

    public function test_delete_event(): void {
        $event_id = 123;
        $expected_response = ['success' => true];

        $this->client_mock
            ->expects($this->once())
            ->method('delete')
            ->with("events/{$event_id}")
            ->willReturn($expected_response);

        $result = $this->endpoints->delete_event($event_id);
        $this->assertEquals($expected_response, $result);
    }

    public function test_get_sessions(): void {
        $event_id = 123;
        $expected_response = [
            ['id' => 1, 'event_id' => $event_id],
            ['id' => 2, 'event_id' => $event_id]
        ];

        $this->client_mock
            ->expects($this->once())
            ->method('get')
            ->with("events/{$event_id}/sessions", ['limit' => 20])
            ->willReturn($expected_response);

        $result = $this->endpoints->get_sessions($event_id, ['limit' => 20]);
        $this->assertEquals($expected_response, $result);
    }

    public function test_get_session(): void {
        $session_id = 456;
        $expected_response = ['id' => $session_id, 'name' => 'Test Session'];

        $this->client_mock
            ->expects($this->once())
            ->method('get')
            ->with("sessions/{$session_id}")
            ->willReturn($expected_response);

        $result = $this->endpoints->get_session($session_id);
        $this->assertEquals($expected_response, $result);
    }

    public function test_get_orders(): void {
        $expected_response = [
            ['id' => 1, 'status' => 'completed'],
            ['id' => 2, 'status' => 'pending']
        ];

        $this->client_mock
            ->expects($this->once())
            ->method('get')
            ->with('orders', ['status' => 'all'])
            ->willReturn($expected_response);

        $result = $this->endpoints->get_orders(['status' => 'all']);
        $this->assertEquals($expected_response, $result);
    }

    public function test_get_order(): void {
        $order_id = 789;
        $expected_response = ['id' => $order_id, 'status' => 'completed'];

        $this->client_mock
            ->expects($this->once())
            ->method('get')
            ->with("orders/{$order_id}")
            ->willReturn($expected_response);

        $result = $this->endpoints->get_order($order_id);
        $this->assertEquals($expected_response, $result);
    }

    public function test_create_order(): void {
        $order_data = ['customer_id' => 123, 'items' => []];
        $expected_response = ['id' => 999] + $order_data;

        $this->client_mock
            ->expects($this->once())
            ->method('post')
            ->with('orders', $order_data)
            ->willReturn($expected_response);

        $result = $this->endpoints->create_order($order_data);
        $this->assertEquals($expected_response, $result);
    }

    public function test_health_check(): void {
        $expected_response = ['status' => 'healthy', 'timestamp' => time()];

        $this->client_mock
            ->expects($this->once())
            ->method('get')
            ->with('health')
            ->willReturn($expected_response);

        $result = $this->endpoints->health_check();
        $this->assertEquals($expected_response, $result);
    }
} 