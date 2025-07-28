# PHPUnit Setup and Testing Guide

This document explains how to set up and use PHPUnit for testing the Bil24 Connector WordPress plugin.

## 📋 Overview

PHPUnit is configured to run both unit tests (isolated tests without WordPress) and integration tests (with WordPress environment). The setup includes code coverage reporting, test logging, and mock WordPress functions.

## 🏗️ Test Structure

```
tests/
├── bootstrap.php           # Test bootstrap and WordPress mocks
├── Unit/                  # Unit tests (no WordPress dependency)
│   ├── Models/
│   │   └── EventTest.php  # Event model tests
│   └── UtilsTest.php      # Utility function tests
└── Integration/           # Integration tests (requires WordPress)
    └── PluginTest.php     # Plugin integration tests
```

## ⚙️ Configuration Files

### `phpunit.xml`
- **Bootstrap**: `tests/bootstrap.php`
- **Test Suites**: Unit and Integration
- **Code Coverage**: HTML, XML, and text reports
- **Logging**: TestDox HTML and text output
- **Constants**: WordPress test environment variables

### `tests/bootstrap.php`
- **Autoloading**: Composer autoloader integration
- **WordPress Mocks**: Mock functions for unit testing
- **Constants**: Plugin-specific test constants
- **Environment Detection**: Automatic WordPress test suite detection

## 🚀 Installation

### Option 1: With Composer (Recommended)
```bash
# Install all dependencies including PHPUnit
composer install

# Run tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/Unit/Models/EventTest.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage-html
```

### Option 2: Standalone PHPUnit
```bash
# Download PHPUnit PHAR
curl -L https://phar.phpunit.de/phpunit-10.phar -o phpunit.phar

# Run tests
php phpunit.phar

# Run specific test
php phpunit.phar tests/Unit/Models/EventTest.php
```

### Option 3: Manual Testing (No PHPUnit)
```bash
# Quick functionality test
php test_working.php
```

## 📝 Writing Tests

### Unit Test Example
```php
<?php
namespace Bil24\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Bil24\Models\Event;

class EventTest extends TestCase
{
    public function testEventCreation(): void
    {
        $event = new Event(['title' => 'Test Event']);
        $this->assertEquals('Test Event', $event->getTitle());
    }
}
```

### Integration Test Example
```php
<?php
namespace Bil24\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Bil24\Plugin;

class PluginTest extends TestCase
{
    public function testPluginInitialization(): void
    {
        $plugin = Plugin::getInstance();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }
}
```

## 🧪 Test Categories

### Unit Tests (`tests/Unit/`)
- **Purpose**: Test individual classes and methods in isolation
- **Environment**: No WordPress dependencies
- **Speed**: Fast execution
- **Coverage**: Individual method logic, validation, calculations

### Integration Tests (`tests/Integration/`)
- **Purpose**: Test component interaction and WordPress integration
- **Environment**: May require WordPress test suite
- **Speed**: Slower execution
- **Coverage**: Plugin initialization, hooks, database operations

## 📊 Code Coverage

Generate code coverage reports to see which parts of your code are tested:

```bash
# HTML coverage report
vendor/bin/phpunit --coverage-html coverage-html

# Open coverage-html/index.html in your browser
```

Coverage includes:
- **Line Coverage**: Which lines are executed
- **Method Coverage**: Which methods are called
- **Class Coverage**: Which classes are instantiated

## 🎯 Best Practices

### Test Naming
- Test files: `*Test.php`
- Test classes: `*Test`
- Test methods: `test*` or use `@test` annotation

### Test Organization
- One test class per production class
- Group related tests in the same class
- Use descriptive test method names

### Assertions
```php
// Use specific assertions
$this->assertTrue($result);
$this->assertEquals($expected, $actual);
$this->assertInstanceOf(MyClass::class, $object);

// Test exceptions
$this->expectException(\InvalidArgumentException::class);
```

### WordPress Compatibility
```php
// Skip tests when WordPress is not available
if (!function_exists('add_action')) {
    $this->markTestSkipped('WordPress functions not available');
}
```

## 🔧 Composer Scripts

The following scripts are available in `composer.json`:

```bash
composer test           # Run all tests
composer phpcs          # Run code style checks
composer phpcbf         # Fix code style issues
composer check          # Run both tests and code style checks
```

## 🐛 Troubleshooting

### Common Issues

1. **Classes not found**
   - Ensure `composer dump-autoload` has been run
   - Check namespace declarations
   - Verify file paths in autoloader

2. **WordPress functions not available**
   - Unit tests use mock functions from `tests/bootstrap.php`
   - Integration tests require WordPress test suite setup

3. **Permission errors**
   - Ensure write permissions for coverage and log directories
   - Check that `.phpunit.cache` directory is writable

4. **Memory limits**
   - Increase PHP memory limit: `php -d memory_limit=512M phpunit.phar`

### Environment Setup

For integration tests with WordPress:
```bash
# Set WordPress test directory
export WP_TESTS_DIR=/path/to/wordpress-tests-lib

# Or set in phpunit.xml
<env name="WP_TESTS_DIR" value="/path/to/wordpress-tests-lib"/>
```

## 📈 Continuous Integration

Example GitHub Actions workflow:
```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.0'
      - run: composer install
      - run: composer test
```

## ✅ Verification

To verify your PHPUnit setup is working correctly:

1. **Run the verification script**:
   ```bash
   php test_working.php
   ```

2. **Run a simple test**:
   ```bash
   vendor/bin/phpunit tests/Unit/UtilsTest.php
   ```

3. **Check coverage generation**:
   ```bash
   vendor/bin/phpunit --coverage-text
   ```

## 📚 Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WordPress Plugin Testing](https://developer.wordpress.org/plugins/testing/)
- [WordPress Coding Standards](https://github.com/WordPress/WordPress-Coding-Standards)
- [Composer Documentation](https://getcomposer.org/doc/)

---

**Status**: ✅ PHPUnit setup complete and ready for use! 