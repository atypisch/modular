# Modular CMS - Architecture Documentation

## System Overview

Modular CMS is a flexible, component-based content management system built with PHP 8.1+, MySQL 8+, and vanilla JavaScript. It follows a modular architecture that allows for extensibility through plugins and themes.

## Core Architecture

### Directory Structure

```
modular/
├── admin/              # Admin interface
│   ├── api/            # API endpoints
│   ├── assets/         # CSS, JS, images
│   ├── auth/           # Authentication pages
│   ├── dashboard/      # Dashboard pages
│   ├── post-types/     # Post type management
│   ├── posts/          # Post management
│   ├── media/          # Media library
│   ├── users/          # User management
│   ├── settings/       # Settings pages
│   └── templates/      # Admin templates
├── core/               # Core system
│   ├── classes/        # Core classes
│   ├── functions/      # Helper functions
│   └── migrations/     # Database migrations
├── modules/             # Plugins/modules
├── public/             # Public-facing files
│   ├── themes/         # Themes
│   └── uploads/        # Uploaded files
└── tests/              # Test suite
```

## Database Schema

### Core Tables

- **users**: User accounts with authentication
- **roles**: User roles (admin, editor, etc.)
- **capabilities**: Granular permissions
- **role_capabilities**: Role-permission mapping
- **user_roles**: User-role assignment
- **post_types**: Custom post type definitions
- **posts**: Content items
- **component_definitions**: Component field definitions
- **media**: Media files with variants
- **relationships**: Post-to-post relationships
- **taxonomies**: Taxonomy definitions
- **terms**: Taxonomy terms
- **forms**: Form definitions
- **form_submissions**: Form submissions
- **actions**: Automation actions
- **action_queue**: Background job queue
- **settings**: System settings
- **post_revisions**: Post revision history
- **activity_log**: Audit trail

## Component System

### Component Types

Components are stored as JSON in the `component_data` column of posts. Each component has:
- `type`: Component type identifier
- `data`: Component-specific data
- `settings`: Component settings

### Fixed vs Flexible

- **Fixed**: Schema-based fields defined per post type
- **Flexible**: Drag-and-drop builder for composing pages

## Plugin System

Plugins are located in `/modules` and loaded via `PluginManager`. They can:
- Register hooks (actions & filters)
- Add routes
- Register admin UI entries
- Run migrations
- Schedule tasks

## REST API

All API endpoints are under `/api/v1/`:
- `/auth/*` - Authentication
- `/post-types/*` - Post type CRUD
- `/posts/*` - Post CRUD
- `/media/*` - Media operations
- `/users/*` - User management

## Security

- Prepared statements for all queries
- JWT authentication
- CSRF protection (to be implemented)
- XSS sanitization
- Rate limiting (to be implemented)

## Performance

- Database indexes on common queries
- Caching layer (to be implemented)
- Background job queue
- Image variant generation


