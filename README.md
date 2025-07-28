# Bil24 Connector for WordPress

[![WordPress](https://img.shields.io/badge/WordPress-6.2%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)](https://php.net/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-Compatible-orange.svg)](https://woocommerce.com/)
[![License](https://img.shields.io/badge/License-GPL--2.0%2B-green.svg)](LICENSE)

**Professional WordPress plugin for seamless integration between [Bil24 ticketing platform](https://bil24.pro/) and WordPress/WooCommerce.**

The Bil24 Connector enables real-time synchronization of events, sessions, orders, and customer data between your WordPress website and the Bil24 ticketing system. Built with enterprise-grade architecture featuring robust API client, comprehensive error handling, caching layer, and full WooCommerce integration.

## 🚀 Features

### Core Functionality
- **🔄 Real-time Synchronization**: Bi-directional data sync between Bil24 and WordPress
- **🎫 Event Management**: Import and manage Bil24 events as WordPress posts
- **🛒 WooCommerce Integration**: Full product, order, and customer synchronization
- **📡 Webhook Support**: Automated event processing via WordPress REST API
- **🔒 Secure Authentication**: API key management with token refresh
- **⚡ Performance Optimized**: Built-in caching and connection pooling

### Technical Features
- **🛡️ Error Handling**: Comprehensive retry logic and circuit breaker patterns
- **📊 Monitoring**: Built-in logging, metrics, and admin dashboard
- **🔄 Rate Limiting**: Automatic API quota management
- **🌐 Internationalization**: Multi-language support (i18n ready)
- **🧪 Testing**: Full PHPUnit test suite with CI/CD integration
- **📝 Code Quality**: WordPress Coding Standards compliance

## 📋 Requirements

### Minimum Requirements
- **WordPress**: 6.2 or higher
- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher (or MariaDB 10.3+)
- **WooCommerce**: 7.0+ (optional, required for e-commerce features)

### Recommended Environment
- **PHP**: 8.1+ with OPcache enabled
- **WordPress**: Latest stable version
- **Memory Limit**: 256MB or higher
- **SSL Certificate**: Required for webhook endpoints

## 🔧 Installation

### Option 1: WordPress Admin (Recommended)

1. Download the latest release from [GitHub Releases](https://github.com/yourname/bil24-connector/releases)
2. Upload the plugin via **Plugins → Add New → Upload Plugin**
3. Activate the plugin
4. Configure your Bil24 API credentials

### Option 2: Manual Installation

```bash
# Navigate to your WordPress plugins directory
cd wp-content/plugins/

# Clone the repository
git clone https://github.com/yourname/bil24-connector.git

# Install dependencies
cd bil24-connector
composer install --no-dev

# Set proper permissions
chmod -R 755 .
```

### Option 3: Composer Installation

Add to your WordPress `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/yourname/bil24-connector"
    }
  ],
  "require": {
    "yourname/bil24-connector": "^1.0"
  }
}
```

## ⚙️ Configuration

### 1. Basic Setup

1. Go to **Settings → Bil24 Connector** in WordPress admin
2. Enter your Bil24 API credentials:
   - **API Key**: Your Bil24 API key
   - **Secret Key**: Your Bil24 secret key
   - **Environment**: Production or Sandbox
3. Click **Test Connection** to verify credentials
4. Save settings

### 2. Webhook Configuration

1. In Bil24 admin panel, configure webhook URL:
   ```
   https://yoursite.com/wp-json/bil24/v1/webhook
   ```
2. Select events to subscribe to:
   - Order created/updated
   - Event created/updated
   - Session created/updated
3. Set webhook secret for security verification

### 3. WooCommerce Integration

If using WooCommerce:

1. Go to **WooCommerce → Settings → Bil24**
2. Configure field mappings:
   - Product fields mapping
   - Order status mapping
   - Customer data mapping
3. Set synchronization preferences:
   - Auto-sync frequency
   - Conflict resolution rules
   - Background processing options

## 📖 Usage

### Event Management

#### Importing Events from Bil24

```php
// Get Bil24 client instance
$client = \Bil24\Plugin::instance()->get_client();

// Import all events
$events = $client->get_events();
foreach ($events as $event_data) {
    $event = new \Bil24\Models\Event($event_data);
    $event->save_as_post();
}
```

#### Creating Events in WordPress

Events are automatically synchronized when created in Bil24. You can also manually trigger sync:

1. Go to **Tools → Bil24 Sync**
2. Select **Import Events**
3. Choose date range or specific events
4. Click **Start Import**

### Order Synchronization

#### Automatic Sync (Recommended)

Orders are automatically synchronized when:
- New order is placed in WooCommerce
- Order status changes
- Bil24 sends webhook notifications

#### Manual Sync

```php
// Sync specific order
$order_id = 123;
$sync_service = new \Bil24\Services\OrderSync();
$result = $sync_service->sync_order($order_id);

if ($result->is_success()) {
    echo "Order synchronized successfully";
}
```

### Using the API Client

```php
// Get authenticated client
$client = \Bil24\Plugin::instance()->get_client();

// Make API calls
$response = $client->get_events([
    'date_from' => '2024-01-01',
    'date_to' => '2024-12-31',
    'status' => 'active'
]);

if ($response->is_success()) {
    $events = $response->get_data();
    // Process events...
}
```

## 🔌 Hooks & Filters

### Actions

```php
// Fired when Bil24 event is imported
do_action('bil24_event_imported', $event_id, $bil24_data);

// Fired when order is synchronized
do_action('bil24_order_synced', $order_id, $sync_result);

// Fired when webhook is received
do_action('bil24_webhook_received', $event_type, $payload);
```

### Filters

```php
// Modify event data before import
$event_data = apply_filters('bil24_import_event_data', $event_data, $bil24_event);

// Customize order sync fields
$sync_fields = apply_filters('bil24_order_sync_fields', $fields, $order);

// Modify API request parameters
$params = apply_filters('bil24_api_request_params', $params, $endpoint);
```

### Custom Event Handlers

```php
// Register custom event handler
add_action('bil24_webhook_received', function($event_type, $payload) {
    if ($event_type === 'custom.event') {
        // Handle custom event
        custom_event_handler($payload);
    }
});
```

## 🛠️ Development

### Development Setup

```bash
# Clone repository
git clone https://github.com/yourname/bil24-connector.git
cd bil24-connector

# Install dependencies
composer install

# Install development tools
npm install

# Set up WordPress test environment
./tests/bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

### Running Tests

```bash
# Run all tests
composer test

# Run specific test suites
vendor/bin/phpunit tests/Unit/
vendor/bin/phpunit tests/Integration/

# Run with coverage
composer test:coverage
```

### Code Quality Tools

```bash
# Check coding standards
composer phpcs

# Auto-fix coding standards
composer phpcbf

# Run static analysis
composer phpstan

# Run all quality checks
composer check
```

### Project Management

This project uses **Taskmaster AI** for development tracking:

```bash
# View current tasks (Windows)
taskmaster.bat list

# View current tasks (Unix/Linux)
php taskmaster.php list

# Get next task to work on
taskmaster.bat next

# Update task status
taskmaster.bat set-status --id=1 --status=done

# Web dashboard
# Open taskmaster-dashboard.php in your browser
```

See `TASKMASTER.md` for complete Taskmaster usage guide.

## 📁 Project Structure

```
bil24-connector/
├── bil24-connector.php          # Main plugin file
├── composer.json                # Dependencies and autoloader
├── phpunit.xml                  # PHPUnit configuration
├── phpcs.xml.dist              # Coding standards config
├── phpstan.neon                # Static analysis config
├── README.md                    # This file
├── STRUCTURE.md                 # Detailed structure docs
├── TASKMASTER.md               # Project management guide
│
├── includes/                    # Core plugin files
│   ├── Plugin.php              # Main plugin class
│   ├── Constants.php           # Plugin constants
│   ├── Utils.php               # Utility functions
│   │
│   ├── api/                    # API layer
│   │   ├── Client.php          # Main API client
│   │   └── Endpoints.php       # API endpoints
│   │
│   ├── models/                 # Data models
│   │   └── Event.php           # Event model
│   │
│   ├── services/               # Business logic
│   │   ├── AuthService.php     # Authentication
│   │   ├── CacheService.php    # Caching layer
│   │   └── LoggingService.php  # Logging
│   │
│   ├── integrations/           # Platform integrations
│   │   ├── EventSync.php       # Event synchronization
│   │   ├── OrderSync.php       # Order synchronization
│   │   ├── SessionSync.php     # Session synchronization
│   │   ├── WooCommerce/        # WooCommerce integration
│   │   └── WordPress/          # WordPress integration
│   │
│   ├── admin/                  # Admin interface
│   │   └── SettingsPage.php    # Settings page
│   │
│   ├── public/                 # Frontend functionality
│   └── frontend/               # Frontend classes
│
├── assets/                     # Static assets
│   ├── css/                    # Stylesheets
│   ├── js/                     # JavaScript files
│   └── images/                 # Images
│
├── templates/                  # Template files
├── languages/                  # Translation files
│
├── tests/                      # Test suites
│   ├── Unit/                   # Unit tests
│   ├── Integration/            # Integration tests
│   └── bootstrap.php           # Test bootstrap
│
├── tools/                      # Development tools
│   ├── phpcs.phar             # Code sniffer
│   ├── phpcbf.phar            # Code beautifier
│   └── wpcs/                  # WordPress coding standards
│
└── vendor/                     # Composer dependencies
```

## 🐛 Troubleshooting

### Common Issues

#### Connection Errors

**Problem**: "Failed to connect to Bil24 API"
**Solution**:
1. Verify API credentials in settings
2. Check if server can make outbound HTTPS requests
3. Ensure proper SSL configuration
4. Check firewall settings

#### Webhook Not Receiving Events

**Problem**: Events not being processed from Bil24
**Solution**:
1. Verify webhook URL is accessible from internet
2. Check SSL certificate validity
3. Ensure WordPress REST API is enabled
4. Verify webhook secret matches configuration

#### Synchronization Issues

**Problem**: Data not syncing between systems
**Solution**:
1. Check error logs in **Tools → Bil24 Logs**
2. Verify API rate limits are not exceeded
3. Check for conflicting plugins
4. Review field mapping configuration

### Debugging

Enable debug mode by adding to `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('BIL24_DEBUG', true);
```

Debug information is logged to:
- WordPress debug log: `/wp-content/debug.log`
- Plugin logs: **Tools → Bil24 Logs** in admin

### Performance Optimization

1. **Enable Caching**: Configure object cache (Redis/Memcached)
2. **Optimize Database**: Ensure proper indexing on large tables
3. **Background Processing**: Enable WordPress cron for large sync operations
4. **Rate Limiting**: Adjust API rate limits based on your plan

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details.

### Development Workflow

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes following coding standards
4. Add tests for new functionality
5. Run quality checks: `composer check`
6. Commit changes: `git commit -m 'Add amazing feature'`
7. Push to branch: `git push origin feature/amazing-feature`
8. Submit a Pull Request

### Coding Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- Use PSR-4 autoloading
- Write comprehensive tests
- Document all public methods
- Use meaningful commit messages

## 📄 License

This project is licensed under the GNU General Public License v2.0 or later. See the [LICENSE](LICENSE) file for details.

## 🆘 Support

### Documentation
- [Plugin Documentation](https://github.com/yourname/bil24-connector/wiki)
- [API Reference](https://bil24.pro/docs/api)
- [WordPress Codex](https://codex.wordpress.org/)
- [WooCommerce Documentation](https://docs.woocommerce.com/)

### Community Support
- [GitHub Issues](https://github.com/yourname/bil24-connector/issues)
- [WordPress Support Forum](https://wordpress.org/support/plugin/bil24-connector/)
- [Stack Overflow](https://stackoverflow.com/questions/tagged/bil24)

### Professional Support
For professional support, custom development, or enterprise features:
- Email: support@yourwebsite.com
- Website: https://yourwebsite.com/contact

## 🏷️ Tags

`wordpress` `plugin` `bil24` `ticketing` `woocommerce` `api` `integration` `events` `synchronization` `webhook`

---

**Made with ❤️ for the WordPress community**
