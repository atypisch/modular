# Modular CMS

A flexible, component-based content management system built with PHP, MySQL, and optional Vue for interactive components.

## Architecture

Modular uses exactly three top-level application folders:

| Folder | Purpose |
|--------|--------|
| **/admin** | Dashboard UI, admin controllers, admin auth, settings UI, admin-configurable settings in `admin/config/`. |
| **/core** | Framework logic, database connection, models, API, hooks, classes, internal services. System configuration lives in `core/config/`. Runtime storage (logs, cache, backups) lives in `core/storage/`. |
| **/public** | Front-end rendering, themes, public routes, front-end assets. Entry point: `public/index.php`. |

- **No SPA.** PHP handles template rendering (SSR). Vue is used only for interactive components; it does not control routing.
- **Themes and models** are maintained in separate repositories; they are excluded from this repo via `.gitignore` (`public/themes/`, `core/models/`).
- **docs/** and **design/** are development-only and not part of the deployed application; they are in `.gitignore`.

## Features

- Component-based architecture (fixed and flexible components)
- Custom post types with full CRUD
- Media library with variants
- Forms builder with spam protection
- Extensible hooks and module system
- REST API for headless use
- Hybrid SSR + Vue (PHP templates, Vue for interactivity only)
- Role-based access control
- Caching and update system

## Requirements

- PHP >= 8.1
- MySQL >= 8.0

## Installation

1. Clone the repo. Clone theme and model repos into `public/themes/` and `core/models/` as needed.
2. Point your web server document root to `public/`.
3. Copy `admin/config/config.local.php.example` to `admin/config/config.local.php` and set database credentials.
4. Run the install flow via `/admin/setup/` or apply `core/migrations/full_schema.sql` and create an admin user.

## Documentation

Internal documentation is available at **`/documentation`** on the front (overview, API, hooks, template system, theme development). Content is in `core/documentation/`. The `docs/` directory in the repo is development-only and gitignored in production.

## License

MIT
