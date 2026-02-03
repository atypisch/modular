# Installation Guide

## Database Installation

The database tables need to be created before using the CMS. Follow these steps:

### Option 1: Web Interface (Recommended)

1. Navigate to `/modular/admin/install.php` in your browser
2. Review the database status
3. Click "Install Database Tables" button
4. Wait for the installation to complete
5. You will see a success message with default admin credentials:
   - Username: `admin`
   - Password: `admin`
   - **IMPORTANT**: Change these credentials immediately after first login!

### Option 2: Command Line

If you have PHP CLI available:

```bash
cd /Users/martentiman/Sites/modular
php admin/setup/install_database.php
```

### Database Configuration

The database configuration is in `admin/config/config.php`:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306'); // Change to 8889 for MAMP
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'modular');
```

**For MAMP users**: Change `DB_PORT` to `8889` in `admin/config/config.php`.

## Tables Created

The installation creates the following tables:

- `users` - User accounts
- `roles` - User roles
- `capabilities` - Permissions
- `role_capabilities` - Role-permission mapping
- `user_roles` - User-role mapping
- `post_types` - Custom post types
- `posts` - Content posts
- `component_definitions` - Component schemas
- `media` - Media library
- `relationships` - Content relationships
- `taxonomies` - Taxonomy definitions
- `terms` - Taxonomy terms
- `post_terms` - Post-term relationships
- `forms` - Form definitions
- `form_submissions` - Form submissions
- `actions` - Automation actions
- `action_queue` - Action queue
- `settings` - Site settings
- `post_revisions` - Post revision history
- `activity_log` - Activity logging
- `schema_migrations` - Migration tracking

## Troubleshooting

### Error: "Table 'modular.post_types' doesn't exist"

This means the database tables haven't been created yet. Run the installation script.

### Error: "Database connection failed"

1. Check your database configuration in `admin/config/config.php`
2. Ensure MySQL/MariaDB is running
3. For MAMP, ensure the port is set to 8889
4. Verify the database `modular` exists

### Error: "Migration already executed"

If you need to reinstall, you can:
1. Drop all tables manually
2. Or remove the migration record from `schema_migrations` table
3. Then run the installation again

## Next Steps

After installation:

1. Login at `/modular/admin/auth/login.php`
2. Change the default admin password
3. Create your first post type
4. Start creating content!


