# Modular CMS - Modules System

## Overview

The Modules system allows you to extend Modular CMS functionality with WordPress-style plugins. Modules are stored in `/public/modules/` and can be activated/deactivated through the admin interface.

## Features

- **WordPress-style Plugin System**: Similar to WordPress plugins
- **Hook System**: Actions and filters for extensibility
- **Easy Installation**: Just place modules in `/public/modules/`
- **Admin Interface**: Manage modules through the admin panel
- **Database Integration**: Module settings stored in database
- **Activation/Deactivation Hooks**: Modules can run code on activation/deactivation

## Module Structure

Each module should be in its own folder under `/public/modules/`:

```
/public/modules/
  └── my-module/
      ├── module.php          (Required - main module file)
      ├── migrations/         (Optional - database migrations)
      │   └── 001_create_tables.php
      └── assets/             (Optional - CSS, JS, images)
          ├── css/
          ├── js/
          └── images/
```

## Creating a Module

### 1. Create Module Folder

Create a folder in `/public/modules/` (e.g., `/public/modules/my-module/`)

### 2. Create module.php

Create a `module.php` file with a header comment:

```php
<?php
/**
 * Module Name: My Module
 * Version: 1.0.0
 * Description: This is my custom module
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: my-module
 * Requires PHP: 8.1
 * Requires Modular: 1.0
 */

// Your module code here
```

### 3. Use Hooks

Modules can use actions and filters:

```php
// Actions (do something)
add_action('init', function() {
    // Run on initialization
});

add_action('module_activate', function($slug) {
    if ($slug === 'my-module') {
        // Run when module is activated
    }
});

// Filters (modify something)
add_filter('the_title', function($title) {
    return $title . ' - Modified';
});
```

## Available Hooks

### Actions

- `init` - Run on system initialization
- `module_activate` - Run when a module is activated
- `module_deactivate` - Run when a module is deactivated
- `admin_init` - Run on admin initialization
- `admin_menu` - Run when admin menu is built

### Filters

- `the_title` - Filter post/page title
- `the_content` - Filter post/page content
- `admin_menu_items` - Filter admin menu items

## Module Manager

The `ModuleManager` class handles all module operations:

```php
$moduleManager = new \Modular\ModuleManager($db);

// Get all modules
$modules = $moduleManager->getModules();

// Get active modules
$activeModules = $moduleManager->getActiveModules();

// Activate a module
$moduleManager->activateModule('my-module');

// Deactivate a module
$moduleManager->deactivateModule('my-module');
```

## Database

Modules are stored in the `modules` table:

- `name` - Module name
- `slug` - Module slug (unique)
- `version` - Module version
- `description` - Module description
- `author` - Author name
- `active` - Whether module is active (0 or 1)
- `settings` - JSON settings

## Admin Interface

Access modules management at `/modular/admin/modules`

Features:
- View all installed modules
- Activate/Deactivate modules
- See module information (version, author, description)
- Development guide

## Example Module

See `/public/modules/example-module/` for a complete example.

## Installation

1. Run the database migration to create the `modules` table:
   - Visit `/modular/admin/install-modules-table.php`
   - Or run the main install script: `/modular/admin/install.php`

2. Place your modules in `/public/modules/`

3. Go to `/modular/admin/modules` to manage them

## Best Practices

1. **Use Namespaces**: Avoid conflicts with other modules
2. **Check Dependencies**: Use `Requires PHP` and `Requires Modular` headers
3. **Handle Errors**: Use try-catch blocks for database operations
4. **Use Hooks**: Don't modify core files, use hooks instead
5. **Version Control**: Keep track of module versions
6. **Documentation**: Document your module's functionality

## Troubleshooting

### Module not appearing
- Check that `module.php` exists in the module folder
- Verify the module header is correctly formatted
- Check file permissions

### Module not activating
- Check PHP errors in error log
- Verify database connection
- Check module syntax errors

### Hooks not working
- Ensure module is activated
- Verify hook names are correct
- Check that hooks are registered before they're called

