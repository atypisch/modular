# Quick Start Guide

## Installation

1. **Clone/Download** the project
2. **Install dependencies**: `composer install`
3. **Configure database** in `admin/config/config.php`
4. **Run migrations**: `php core/migrations/001_core_schema.php`
5. **Start development server**: `php -S localhost:8000 -t public`

## First Steps

1. **Register a user**: Visit `/modular/admin/auth/register.php`
2. **Login**: Visit `/modular/admin/auth/login.php`
3. **Access admin**: Visit `/modular/admin/`

## API Usage

### Authentication

```bash
# Login
curl -X POST http://localhost:8000/modular/admin/api/index.php/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# Use token
curl http://localhost:8000/modular/admin/api/index.php/api/v1/post-types \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Create Post Type

```bash
curl -X POST http://localhost:8000/modular/admin/api/index.php/api/v1/post-types \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "News",
    "slug": "news",
    "description": "News articles",
    "public": true
  }'
```

## Development

- **API Base URL**: `/modular/admin/api/index.php`
- **Admin Base URL**: `/modular/admin/`
- **Public Base URL**: `/modular/public/`

## Next Development Tasks

See `IMPLEMENTATION_SUMMARY.md` for remaining work.

