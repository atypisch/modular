# Modular CMS - Developer Guide

## Architecture Overview

Modular CMS follows a modular, component-based architecture with clear separation of concerns.

### Directory Structure
```
modular/
├── admin/          # Admin interface
├── core/           # Core system classes
├── modules/         # Plugins/modules
├── public/         # Public-facing files
├── tests/          # Test suite
└── cache/          # Cache files
```

## Core Classes

### Database
- `Migration` - Database migrations
- All classes use PDO with prepared statements

### Authentication
- `JWT` - JWT token handling
- `AuthController` - Auth endpoints

### Content
- `PostType` - Post type management
- `Post` - Post management
- `ComponentManager` - Component system
- `ComponentTypes` - Component registry

### Media
- `MediaController` - Media upload & processing

### Forms
- `FormsController` - Form management
- Spam protection built-in

### Actions
- `ActionManager` - Workflow automation
- `ActionsController` - Actions API

### Theme
- `Theme` - Template rendering
- Supports headless mode

### Utilities
- `Cache` - Caching system
- `Hooks` - Plugin hook system
- `i18n` - Internationalization
- `RateLimiter` - Rate limiting

## Plugin Development

### Creating a Plugin

1. Create directory in `/modules/YourPlugin/`
2. Create `module.php`:

```php
<?php
namespace Modular\Plugins\YourPlugin;

use Modular\Hooks;

// Register hooks
Hooks::addAction('plugin_activate', function($pluginName) {
    if ($pluginName === 'YourPlugin') {
        // Activation logic
    }
});

Hooks::addFilter('component_data', function($data, $component) {
    // Modify component data
    return $data;
}, 10);
```

### Available Hooks

**Actions:**
- `plugin_activate` - Plugin activated
- `plugin_deactivate` - Plugin deactivated
- `post_created` - Post created
- `post_updated` - Post updated
- `form_submitted` - Form submitted
- `component_data` - Filter component data
- `render_component` - Filter component rendering

## Component Development

### Creating Custom Components

Components are defined in `ComponentTypes::getTypes()`. To add a custom component:

```php
use Modular\Components\ComponentTypes;

$types = ComponentTypes::getTypes();
$types['my_component'] = [
    'label' => 'My Component',
    'icon' => 'star',
    'schema' => [
        'title' => ['type' => 'string', 'required' => true],
        'content' => ['type' => 'text']
    ],
    'storage' => 'json',
    'validation' => []
];
```

### Component Rendering

Components are rendered via `ComponentManager::render()`. Custom rendering can be added via hooks:

```php
Hooks::addFilter('render_component', function($output, $componentData, $component) {
    if ($component['type'] === 'my_component') {
        return renderMyComponent($componentData);
    }
    return $output;
}, 10);
```

## API Development

### Adding New Endpoints

1. Create controller in `core/classes/`:

```php
namespace Modular\API;

class MyController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function index() {
        // Your logic
    }
}
```

2. Register in `admin/api/index.php`:

```php
$router->addRoute('GET', '/api/v1/my-endpoint', function() use ($GLOBALS) {
    $controller = new MyController($GLOBALS['db']);
    return $controller->index();
}, ['auth']);
```

## Database Migrations

### Creating a Migration

1. Create file: `core/migrations/002_my_migration.php`
2. Use Migration class:

```php
$migration = new Migration($GLOBALS['db']);
$migration->run('002', 'my_migration', $sql);
```

## Testing

### Running Tests

```bash
composer test
```

### Writing Tests

Create test in `tests/Unit/`:

```php
namespace Modular\Tests;

class MyTest extends BaseTestCase {
    public function testSomething() {
        $this->assertTrue(true);
    }
}
```

## Best Practices

1. **Always use prepared statements** for database queries
2. **Sanitize user input** before processing
3. **Escape output** when displaying user data
4. **Use hooks** for extensibility
5. **Follow PSR-12** coding standards
6. **Write tests** for new features
7. **Document** your code

## Security Guidelines

1. Never trust user input
2. Always validate and sanitize
3. Use prepared statements
4. Implement rate limiting
5. Use HTTPS in production
6. Keep dependencies updated
7. Regular security audits

## Performance Tips

1. Use caching for expensive operations
2. Optimize database queries
3. Use indexes on frequently queried columns
4. Implement pagination for large datasets
5. Use background jobs for heavy tasks
6. Optimize images before upload

## Troubleshooting

### Common Issues

**Database connection failed:**
- Check config in `admin/config/config.php`
- Verify MySQL is running
- Check credentials

**API returns 401:**
- Check token is valid
- Verify token in Authorization header
- Check token expiration

**Component not rendering:**
- Check component type is registered
- Verify component_data format
- Check for JavaScript errors

## Getting Help

- Check documentation in `/docs`
- Review code examples in core classes
- Check GitHub issues
- Review test files for usage examples


