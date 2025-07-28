<?php

namespace Bil24\Tests\Unit\Models;

use Bil24\Models\Order;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Order model
 */
class OrderTest extends TestCase {

    private Order $order;

    protected function setUp(): void {
        parent::setUp();
        $this->order = new Order();
    }

    public function test_order_initialization(): void {
        $this->assertInstanceOf(Order::class, $this->order);
        $this->assertNull($this->order->getId());
        $this->assertEmpty($this->order->getItems());
    }

    public function test_set_and_get_id(): void {
        $id = 123;
        $result = $this->order->setId($id);
        
        $this->assertSame($this->order, $result); // Test fluent interface
        $this->assertEquals($id, $this->order->getId());
    }

    public function test_set_and_get_bil24_id(): void {
        $bil24_id = 456;
        $result = $this->order->setBil24Id($bil24_id);
        
        $this->assertSame($this->order, $result);
        $this->assertEquals($bil24_id, $this->order->getBil24Id());
    }

    public function test_set_and_get_status(): void {
        $status = 'completed';
        $result = $this->order->setStatus($status);
        
        $this->assertSame($this->order, $result);
        $this->assertEquals($status, $this->order->getStatus());
    }

    public function test_set_invalid_status(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->order->setStatus('invalid_status');
    }

    public function test_set_and_get_customer_email(): void {
        $email = 'test@example.com';
        $result = $this->order->setCustomerEmail($email);
        
        $this->assertSame($this->order, $result);
        $this->assertEquals($email, $this->order->getCustomerEmail());
    }

    public function test_set_invalid_email(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->order->setCustomerEmail('invalid-email');
    }

    public function test_set_and_get_total_amount(): void {
        $amount = 99.99;
        $result = $this->order->setTotalAmount($amount);
        
        $this->assertSame($this->order, $result);
        $this->assertEquals($amount, $this->order->getTotalAmount());
    }

    public function test_set_negative_total_amount(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->order->setTotalAmount(-10.0);
    }

    public function test_set_and_get_currency(): void {
        $currency = 'EUR';
        $result = $this->order->setCurrency($currency);
        
        $this->assertSame($this->order, $result);
        $this->assertEquals($currency, $this->order->getCurrency());
    }

    public function test_set_invalid_currency(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->order->setCurrency('INVALID');
    }

    public function test_add_item(): void {
        $item = [
            'product_id' => 123,
            'quantity' => 2,
            'price' => 50.0
        ];
        
        $result = $this->order->addItem($item);
        $this->assertSame($this->order, $result);
        
        $items = $this->order->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals($item, $items[0]);
    }

    public function test_remove_item(): void {
        $item1 = ['product_id' => 123, 'quantity' => 1, 'price' => 25.0];
        $item2 = ['product_id' => 456, 'quantity' => 2, 'price' => 50.0];
        
        $this->order->addItem($item1);
        $this->order->addItem($item2);
        
        $result = $this->order->removeItem(0);
        $this->assertSame($this->order, $result);
        
        $items = $this->order->getItems();
        $this->assertCount(1, $items);
        $this->assertEquals($item2, $items[0]);
    }

    public function test_calculate_total(): void {
        $item1 = ['product_id' => 123, 'quantity' => 2, 'price' => 25.0];
        $item2 = ['product_id' => 456, 'quantity' => 1, 'price' => 30.0];
        
        $this->order->addItem($item1);
        $this->order->addItem($item2);
        
        $total = $this->order->calculateTotal();
        $expected = (2 * 25.0) + (1 * 30.0); // 80.0
        
        $this->assertEquals($expected, $total);
    }

    public function test_to_array(): void {
        $this->order
            ->setId(123)
            ->setBil24Id(456)
            ->setStatus('pending')
            ->setCustomerEmail('test@example.com')
            ->setTotalAmount(99.99)
            ->setCurrency('USD');
        
        $array = $this->order->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals(123, $array['id']);
        $this->assertEquals(456, $array['bil24_id']);
        $this->assertEquals('pending', $array['status']);
        $this->assertEquals('test@example.com', $array['customer_email']);
        $this->assertEquals(99.99, $array['total_amount']);
        $this->assertEquals('USD', $array['currency']);
    }

    public function test_fill(): void {
        $data = [
            'id' => 789,
            'bil24_id' => 999,
            'status' => 'completed',
            'customer_email' => 'fill@example.com',
            'total_amount' => 150.0,
            'currency' => 'EUR',
            'items' => [
                ['product_id' => 111, 'quantity' => 1, 'price' => 150.0]
            ]
        ];
        
        $result = $this->order->fill($data);
        $this->assertSame($this->order, $result);
        
        $this->assertEquals(789, $this->order->getId());
        $this->assertEquals(999, $this->order->getBil24Id());
        $this->assertEquals('completed', $this->order->getStatus());
        $this->assertEquals('fill@example.com', $this->order->getCustomerEmail());
        $this->assertEquals(150.0, $this->order->getTotalAmount());
        $this->assertEquals('EUR', $this->order->getCurrency());
        $this->assertCount(1, $this->order->getItems());
    }

    public function test_is_empty(): void {
        $this->assertTrue($this->order->isEmpty());
        
        $this->order->setId(123);
        $this->assertFalse($this->order->isEmpty());
    }

    public function test_get_formatted_total(): void {
        $this->order->setTotalAmount(123.45)->setCurrency('USD');
        $formatted = $this->order->getFormattedTotal();
        
        $this->assertStringContainsString('123.45', $formatted);
        $this->assertStringContainsString('USD', $formatted);
    }
} 