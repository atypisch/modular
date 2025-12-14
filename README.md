# Modular CMS

A flexible, component-based content management system built with PHP, MySQL, and vanilla JavaScript.

## Features

- **Component-Based Architecture**: Fixed and flexible component systems
- **Post Types**: Create custom content types with full CRUD
- **Media Library**: Advanced media management with variants and processing
- **Forms Builder**: Drag-and-drop form builder with spam protection
- **Plugin System**: Extensible plugin architecture with hooks
- **REST API**: Full JSON REST API with OpenAPI documentation
- **Theme System**: Flexible theming with headless mode support
- **User Roles**: Granular role-based access control
- **Performance**: Built-in caching and optimization

## Requirements

- PHP >= 8.1
- MySQL >= 8.0
- Composer
- Node.js (for frontend assets, optional)

## Installation

```bash
# Install dependencies
composer install

# Run migrations
composer migrate

# Set up environment
cp .env.example .env
# Edit .env with your database credentials
```

## Development

```bash
# Run tests
composer test

# Run linter
composer lint

# Run static analysis
composer stan
```

## Documentation

See `/docs` directory for:
- `ARCHITECTURE.md` - System architecture
- `API.md` - REST API documentation
- `CONTRIBUTING.md` - Contribution guidelines

## License

MIT

