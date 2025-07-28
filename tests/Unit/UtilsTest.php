<?php
/**
 * Unit tests for Utils class
 *
 * @package Bil24_Connector
 * @subpackage Tests
 */

namespace Bil24\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Bil24\Utils;

/**
 * Test the Utils class
 */
class UtilsTest extends TestCase
{
    /**
     * Test sanitization methods
     */
    public function testSanitization(): void
    {
        // Test that sanitization methods exist and work
        $this->assertTrue(method_exists(Utils::class, 'sanitize_text_field'));
        
        // Basic sanitization test
        $input = '  <script>alert("test")</script>  ';
        $expected = 'alert("test")'; // Should remove tags and trim
        
        $result = Utils::sanitize_text_field($input);
        $this->assertIsString($result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    /**
     * Test validation methods
     */
    public function testValidation(): void
    {
        // Test that validation methods exist
        $this->assertTrue(method_exists(Utils::class, 'is_valid_email'));
        $this->assertTrue(method_exists(Utils::class, 'is_valid_url'));
        
        // Email validation
        $this->assertTrue(Utils::is_valid_email('test@example.com'));
        $this->assertFalse(Utils::is_valid_email('invalid-email'));
        
        // URL validation  
        $this->assertTrue(Utils::is_valid_url('https://example.com'));
        $this->assertFalse(Utils::is_valid_url('not-a-url'));
    }

    /**
     * Test array helper methods
     */
    public function testArrayHelpers(): void
    {
        $testArray = [
            'key1' => 'value1',
            'key2' => [
                'nested' => 'nested_value'
            ]
        ];

        // Test get_array_value method if it exists
        if (method_exists(Utils::class, 'get_array_value')) {
            $this->assertEquals('value1', Utils::get_array_value($testArray, 'key1'));
            $this->assertEquals('default', Utils::get_array_value($testArray, 'nonexistent', 'default'));
        }
    }

    /**
     * Test date formatting methods
     */
    public function testDateFormatting(): void
    {
        if (method_exists(Utils::class, 'format_date')) {
            $testDate = '2024-01-01 12:00:00';
            $result = Utils::format_date($testDate);
            $this->assertIsString($result);
        }
    }

    /**
     * Test error handling methods
     */
    public function testErrorHandling(): void
    {
        if (method_exists(Utils::class, 'log_error')) {
            // Test that log_error method exists and can be called
            $this->assertTrue(method_exists(Utils::class, 'log_error'));
            
            // Test logging (this should not throw an exception)
            $result = Utils::log_error('Test error message');
            $this->assertTrue(true); // If we get here, no exception was thrown
        }
    }

    /**
     * Test utility constants
     */
    public function testConstants(): void
    {
        // Test that we can access plugin constants through Utils if available
        if (method_exists(Utils::class, 'get_plugin_version')) {
            $version = Utils::get_plugin_version();
            $this->assertIsString($version);
            $this->assertNotEmpty($version);
        }
    }
} 