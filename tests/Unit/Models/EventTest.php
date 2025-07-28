<?php
/**
 * Unit tests for Event model
 *
 * @package Bil24_Connector
 * @subpackage Tests
 */

namespace Bil24\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Bil24\Models\Event;

/**
 * Test the Event model class
 */
class EventTest extends TestCase
{
    /**
     * Test event creation with array data
     */
    public function testEventCreationWithArray(): void
    {
        $eventData = [
            'id' => 123,
            'title' => 'Test Event',
            'description' => 'Test Description',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-02',
            'venue' => 'Test Venue',
            'price' => 99.99,
            'currency' => 'USD',
            'status' => 'active'
        ];

        $event = new Event($eventData);

        $this->assertEquals(123, $event->getId());
        $this->assertEquals('Test Event', $event->getTitle());
        $this->assertEquals('Test Description', $event->getDescription());
        $this->assertEquals('2024-01-01', $event->getStartDate());
        $this->assertEquals('2024-01-02', $event->getEndDate());
        $this->assertEquals('Test Venue', $event->getVenue());
        $this->assertEquals(99.99, $event->getPrice());
        $this->assertEquals('USD', $event->getCurrency());
        $this->assertEquals('active', $event->getStatus());
    }

    /**
     * Test event creation with empty data
     */
    public function testEventCreationWithEmptyData(): void
    {
        $event = new Event([]);

        $this->assertNull($event->getId());
        $this->assertEmpty($event->getTitle());
        $this->assertEmpty($event->getDescription());
        $this->assertNull($event->getStartDate());
        $this->assertNull($event->getEndDate());
        $this->assertEmpty($event->getVenue());
        $this->assertEquals(0.0, $event->getPrice());
        $this->assertEquals('USD', $event->getCurrency()); // Default value
        $this->assertEquals('draft', $event->getStatus()); // Default value
    }

    /**
     * Test event setters
     */
    public function testEventSetters(): void
    {
        $event = new Event([]);

        $event->setId(456);
        $event->setTitle('Updated Title');
        $event->setDescription('Updated Description');
        $event->setStartDate('2024-02-01');
        $event->setEndDate('2024-02-02');
        $event->setVenue('Updated Venue');
        $event->setPrice(149.99);
        $event->setCurrency('EUR');
        $event->setStatus('published');

        $this->assertEquals(456, $event->getId());
        $this->assertEquals('Updated Title', $event->getTitle());
        $this->assertEquals('Updated Description', $event->getDescription());
        $this->assertEquals('2024-02-01', $event->getStartDate());
        $this->assertEquals('2024-02-02', $event->getEndDate());
        $this->assertEquals('Updated Venue', $event->getVenue());
        $this->assertEquals(149.99, $event->getPrice());
        $this->assertEquals('EUR', $event->getCurrency());
        $this->assertEquals('published', $event->getStatus());
    }

    /**
     * Test toArray method
     */
    public function testToArray(): void
    {
        $eventData = [
            'id' => 789,
            'title' => 'Array Test Event',
            'description' => 'Array Test Description',
            'start_date' => '2024-03-01',
            'end_date' => '2024-03-02',
            'venue' => 'Array Test Venue',
            'price' => 199.99,
            'currency' => 'GBP',
            'status' => 'published'
        ];

        $event = new Event($eventData);
        $result = $event->toArray();

        $this->assertEquals($eventData, $result);
    }

    /**
     * Test validation with invalid data
     */
    public function testValidationWithInvalidData(): void
    {
        $event = new Event([]);
        
        // Test invalid price
        $event->setPrice(-10.00);
        $this->assertEquals(0.0, $event->getPrice()); // Should default to 0 for negative values
        
        // Test invalid status
        $event->setStatus('invalid_status');
        $this->assertEquals('draft', $event->getStatus()); // Should fallback to default
    }

    /**
     * Test string representation
     */
    public function testStringRepresentation(): void
    {
        $event = new Event([
            'title' => 'String Test Event',
            'start_date' => '2024-04-01'
        ]);

        $expectedString = 'String Test Event (2024-04-01)';
        $this->assertEquals($expectedString, (string) $event);
    }

    /**
     * Test isEmpty method
     */
    public function testIsEmpty(): void
    {
        // Empty event
        $emptyEvent = new Event([]);
        $this->assertTrue($emptyEvent->isEmpty());

        // Event with data
        $event = new Event(['title' => 'Not Empty']);
        $this->assertFalse($event->isEmpty());
    }
} 