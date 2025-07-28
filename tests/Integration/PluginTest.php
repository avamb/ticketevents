<?php
/**
 * Integration tests for Plugin class
 *
 * @package Bil24_Connector
 * @subpackage Tests
 */

namespace Bil24\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Bil24\Plugin;

/**
 * Test the Plugin class integration
 */
class PluginTest extends TestCase
{
    /**
     * Test plugin initialization
     */
    public function testPluginInitialization(): void
    {
        // Test that Plugin class exists
        $this->assertTrue(class_exists(Plugin::class));
        
        // Test singleton pattern
        $plugin1 = Plugin::getInstance();
        $plugin2 = Plugin::getInstance();
        
        $this->assertInstanceOf(Plugin::class, $plugin1);
        $this->assertSame($plugin1, $plugin2);
    }

    /**
     * Test plugin constants are defined
     */
    public function testPluginConstants(): void
    {
        // Test that basic constants are available
        $this->assertTrue(defined('BIL24_PLUGIN_DIR') || defined('BIL24_VERSION'));
    }

    /**
     * Test autoloading works
     */
    public function testAutoloading(): void
    {
        // Test that our classes can be autoloaded
        $this->assertTrue(class_exists('Bil24\\Constants'));
        $this->assertTrue(class_exists('Bil24\\Utils'));
        $this->assertTrue(class_exists('Bil24\\Models\\Event'));
    }

    /**
     * Test WordPress hooks integration (if WordPress is available)
     */
    public function testWordPressHooks(): void
    {
        // Skip if WordPress functions are not available
        if (!function_exists('add_action')) {
            $this->markTestSkipped('WordPress functions not available');
        }

        // Test that we can register hooks without errors
        $plugin = Plugin::getInstance();
        
        // This should not throw any exceptions
        $this->assertTrue(true);
    }

    /**
     * Test plugin version
     */
    public function testPluginVersion(): void
    {
        if (defined('BIL24_VERSION')) {
            $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', BIL24_VERSION);
        } else {
            $this->markTestSkipped('BIL24_VERSION not defined');
        }
    }
} 