<?php
/**
 * Session model tests
 */

namespace Bil24\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Bil24\Models\Session;

class SessionTest extends TestCase {

    public function testSessionCreation() {
        $session = new Session();
        $this->assertInstanceOf( Session::class, $session );
        $this->assertTrue( $session->isEmpty() );
    }

    public function testSessionWithData() {
        $data = [
            'id' => 1,
            'event_id' => 100,
            'title' => 'Evening Session',
            'description' => 'Main evening performance',
            'start_datetime' => '2024-12-01 19:00:00',
            'end_datetime' => '2024-12-01 21:30:00',
            'venue_id' => 50,
            'capacity' => 500,
            'available_seats' => 450,
            'reserved_seats' => 30,
            'sold_seats' => 20,
            'status' => 'active',
            'base_price' => 75.00,
            'currency' => 'USD',
            'bil24_id' => 'bil24_session_123'
        ];

        $session = new Session( $data );

        $this->assertEquals( 1, $session->getId() );
        $this->assertEquals( 100, $session->getEventId() );
        $this->assertEquals( 'Evening Session', $session->getTitle() );
        $this->assertEquals( 'Main evening performance', $session->getDescription() );
        $this->assertEquals( '2024-12-01 19:00:00', $session->getStartDatetime() );
        $this->assertEquals( '2024-12-01 21:30:00', $session->getEndDatetime() );
        $this->assertEquals( 50, $session->getVenueId() );
        $this->assertEquals( 500, $session->getCapacity() );
        $this->assertEquals( 450, $session->getAvailableSeats() );
        $this->assertEquals( 30, $session->getReservedSeats() );
        $this->assertEquals( 20, $session->getSoldSeats() );
        $this->assertEquals( 'active', $session->getStatus() );
        $this->assertEquals( 75.00, $session->getBasePrice() );
        $this->assertEquals( 'USD', $session->getCurrency() );
        $this->assertEquals( 'bil24_session_123', $session->getBil24Id() );
        $this->assertFalse( $session->isEmpty() );
    }

    public function testSetters() {
        $session = new Session();

        $session->setId( 2 );
        $session->setEventId( 200 );
        $session->setTitle( 'Matinee Show' );
        $session->setDescription( 'Afternoon performance' );
        $session->setVenueId( 75 );
        $session->setCapacity( 300 );
        $session->setAvailableSeats( 280 );
        $session->setReservedSeats( 15 );
        $session->setSoldSeats( 5 );
        $session->setStatus( 'scheduled' );
        $session->setBasePrice( 50.00 );
        $session->setCurrency( 'EUR' );
        $session->setBil24Id( 'bil24_session_456' );

        $this->assertEquals( 2, $session->getId() );
        $this->assertEquals( 200, $session->getEventId() );
        $this->assertEquals( 'Matinee Show', $session->getTitle() );
        $this->assertEquals( 'Afternoon performance', $session->getDescription() );
        $this->assertEquals( 75, $session->getVenueId() );
        $this->assertEquals( 300, $session->getCapacity() );
        $this->assertEquals( 280, $session->getAvailableSeats() );
        $this->assertEquals( 15, $session->getReservedSeats() );
        $this->assertEquals( 5, $session->getSoldSeats() );
        $this->assertEquals( 'scheduled', $session->getStatus() );
        $this->assertEquals( 50.00, $session->getBasePrice() );
        $this->assertEquals( 'EUR', $session->getCurrency() );
        $this->assertEquals( 'bil24_session_456', $session->getBil24Id() );
    }

    public function testDateTimeHandling() {
        $session = new Session();
        
        // Test string date
        $session->setStartDatetime( '2024-12-01 19:00:00' );
        $this->assertInstanceOf( \DateTime::class, $session->get_start_datetime() );
        $this->assertEquals( '2024-12-01 19:00:00', $session->getStartDatetime() );

        // Test DateTime object
        $dateTime = new \DateTime( '2024-12-01 21:30:00' );
        $session->setEndDatetime( $dateTime );
        $this->assertInstanceOf( \DateTime::class, $session->get_end_datetime() );
        $this->assertEquals( '2024-12-01 21:30:00', $session->getEndDatetime() );
    }

    public function testStatusValidation() {
        $session = new Session();

        // Valid statuses
        $validStatuses = [ 'scheduled', 'active', 'cancelled', 'completed', 'sold_out' ];
        
        foreach ( $validStatuses as $status ) {
            $session->setStatus( $status );
            $this->assertEquals( $status, $session->getStatus() );
        }

        // Invalid status should not change current value
        $session->setStatus( 'active' );
        $session->setStatus( 'invalid_status' );
        $this->assertEquals( 'active', $session->getStatus() );
    }

    public function testSeatCounts() {
        $session = new Session();

        // Negative values should be converted to 0
        $session->setCapacity( -10 );
        $this->assertEquals( 0, $session->getCapacity() );

        $session->setAvailableSeats( -5 );
        $this->assertEquals( 0, $session->getAvailableSeats() );

        $session->setReservedSeats( -3 );
        $this->assertEquals( 0, $session->getReservedSeats() );

        $session->setSoldSeats( -2 );
        $this->assertEquals( 0, $session->getSoldSeats() );
    }

    public function testUpdateSeatCounts() {
        $session = new Session();
        $session->setStatus( 'active' );

        $session->update_seat_counts( 100, 20, 80 );

        $this->assertEquals( 100, $session->getAvailableSeats() );
        $this->assertEquals( 20, $session->getReservedSeats() );
        $this->assertEquals( 80, $session->getSoldSeats() );
        $this->assertEquals( 'active', $session->getStatus() );

        // Test auto sold out status
        $session->update_seat_counts( 0, 0, 200 );
        $this->assertEquals( 'sold_out', $session->getStatus() );
    }

    public function testAvailabilityChecks() {
        $session = new Session();

        // Test available session
        $session->setStatus( 'active' );
        $session->setAvailableSeats( 50 );
        $this->assertTrue( $session->is_available() );

        // Test sold out session
        $session->setAvailableSeats( 0 );
        $this->assertFalse( $session->is_available() );
        $this->assertTrue( $session->is_sold_out() );

        // Test cancelled session
        $session->setStatus( 'cancelled' );
        $session->setAvailableSeats( 50 );
        $this->assertFalse( $session->is_available() );

        // Test sold_out status
        $session->setStatus( 'sold_out' );
        $this->assertTrue( $session->is_sold_out() );
    }

    public function testPriceHandling() {
        $session = new Session();

        // Negative price should be converted to 0
        $session->setBasePrice( -25.00 );
        $this->assertEquals( 0.0, $session->getBasePrice() );

        $session->setBasePrice( 99.99 );
        $this->assertEquals( 99.99, $session->getBasePrice() );
    }

    public function testCurrencyHandling() {
        $session = new Session();

        $session->setCurrency( 'usd' );
        $this->assertEquals( 'USD', $session->getCurrency() );

        $session->setCurrency( 'eur' );
        $this->assertEquals( 'EUR', $session->getCurrency() );
    }

    public function testToArray() {
        $data = [
            'id' => 1,
            'event_id' => 100,
            'title' => 'Test Session',
            'status' => 'active',
            'base_price' => 50.00
        ];

        $session = new Session( $data );
        $array = $session->toArray();

        $this->assertIsArray( $array );
        $this->assertEquals( 1, $array['id'] );
        $this->assertEquals( 100, $array['event_id'] );
        $this->assertEquals( 'Test Session', $array['title'] );
        $this->assertEquals( 'active', $array['status'] );
        $this->assertEquals( 50.00, $array['base_price'] );
    }

    public function testStringRepresentation() {
        $session = new Session();
        $session->setTitle( 'Evening Show' );
        $session->setStartDatetime( '2024-12-01 19:00:00' );

        $string = (string) $session;
        $this->assertEquals( 'Evening Show (2024-12-01 19:00)', $string );

        // Test without datetime
        $session2 = new Session();
        $session2->setTitle( 'Test Session' );
        $string2 = (string) $session2;
        $this->assertEquals( 'Test Session', $string2 );
    }

    public function testLastSyncTimestamp() {
        $session = new Session();

        // Test string timestamp
        $session->set_last_sync( '2024-12-01 10:30:00' );
        $this->assertInstanceOf( \DateTime::class, $session->get_last_sync() );

        // Test DateTime object
        $dateTime = new \DateTime();
        $session->set_last_sync( $dateTime );
        $this->assertEquals( $dateTime, $session->get_last_sync() );

        // Test integer timestamp
        $timestamp = time();
        $session->set_last_sync( $timestamp );
        $this->assertInstanceOf( \DateTime::class, $session->get_last_sync() );
    }
} 