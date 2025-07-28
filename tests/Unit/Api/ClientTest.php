<?php

namespace Bil24\Tests\Unit\Api;

use Bil24\Api\Client;
use Bil24\Constants;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for API Client
 */
class ClientTest extends TestCase {

    private Client $client;

    protected function setUp(): void {
        parent::setUp();
        $this->client = new Client();
    }

    public function test_client_initialization(): void {
        $this->assertInstanceOf(Client::class, $this->client);
    }

    public function test_set_credentials(): void {
        $fid = 'test_fid';
        $token = 'test_token';

        $this->client->set_credentials($fid, $token);

        // Используем рефлексию для проверки private свойств
        $reflection = new \ReflectionClass($this->client);
        
        $fid_property = $reflection->getProperty('fid');
        $fid_property->setAccessible(true);
        $this->assertEquals($fid, $fid_property->getValue($this->client));

        $token_property = $reflection->getProperty('token');
        $token_property->setAccessible(true);
        $this->assertEquals($token, $token_property->getValue($this->client));
    }

    public function test_build_url(): void {
        $reflection = new \ReflectionClass($this->client);
        $method = $reflection->getMethod('build_url');
        $method->setAccessible(true);

        $endpoint = 'events';
        $params = ['limit' => 10, 'offset' => 0];

        $url = $method->invoke($this->client, $endpoint, $params);

        $this->assertStringContainsString($endpoint, $url);
        $this->assertStringContainsString('limit=10', $url);
        $this->assertStringContainsString('offset=0', $url);
    }

    public function test_get_cache_key(): void {
        $reflection = new \ReflectionClass($this->client);
        $method = $reflection->getMethod('get_cache_key');
        $method->setAccessible(true);

        $endpoint = 'events';
        $params = ['limit' => 10];

        $cache_key = $method->invoke($this->client, $endpoint, $params);

        $this->assertStringStartsWith(Constants::CACHE_PREFIX, $cache_key);
        $this->assertStringContainsString($endpoint, $cache_key);
    }

    public function test_validate_response_with_valid_data(): void {
        $reflection = new \ReflectionClass($this->client);
        $method = $reflection->getMethod('validate_response');
        $method->setAccessible(true);

        $valid_response = [
            'status' => 'success',
            'data' => ['id' => 1, 'name' => 'Test Event']
        ];

        $result = $method->invoke($this->client, $valid_response);
        $this->assertEquals($valid_response['data'], $result);
    }

    public function test_validate_response_with_error(): void {
        $reflection = new \ReflectionClass($this->client);
        $method = $reflection->getMethod('validate_response');
        $method->setAccessible(true);

        $error_response = [
            'status' => 'error',
            'message' => 'API Error'
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API Error');

        $method->invoke($this->client, $error_response);
    }

    public function test_sanitize_response(): void {
        $reflection = new \ReflectionClass($this->client);
        $method = $reflection->getMethod('sanitize_response');
        $method->setAccessible(true);

        $response = [
            'id' => 1,
            'name' => '<script>alert("xss")</script>Clean Name',
            'description' => 'Clean description'
        ];

        $sanitized = $method->invoke($this->client, $response);

        $this->assertEquals(1, $sanitized['id']);
        $this->assertStringNotContainsString('<script>', $sanitized['name']);
        $this->assertStringContainsString('Clean Name', $sanitized['name']);
    }

    public function test_is_authenticated_with_credentials(): void {
        $this->client->set_credentials('test_fid', 'test_token');
        $this->assertTrue($this->client->is_authenticated());
    }

    public function test_is_authenticated_without_credentials(): void {
        $this->assertFalse($this->client->is_authenticated());
    }

    public function test_prepare_headers(): void {
        $this->client->set_credentials('test_fid', 'test_token');

        $reflection = new \ReflectionClass($this->client);
        $method = $reflection->getMethod('prepare_headers');
        $method->setAccessible(true);

        $headers = $method->invoke($this->client);

        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals('application/json', $headers['Content-Type']);
        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertStringContainsString('Bearer', $headers['Authorization']);
    }
} 