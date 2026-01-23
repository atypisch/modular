# Contributing to Modular CMS

## Development Setup

1. Clone the repository
2. Install dependencies: `composer install`
3. Set up database and configure `.env`
4. Run migrations: `composer migrate`
5. Start development server

## Code Style

- PHP: Follow PSR-12
- JavaScript: ESLint recommended rules
- Use prepared statements for all database queries
- Sanitize all user input
- Escape all output

## Testing

- Write unit tests for new features
- Write integration tests for API endpoints
- Run `composer test` before committing

## Pull Request Process

1. Create a feature branch
2. Implement feature with tests
3. Ensure all tests pass
4. Update documentation
5. Submit PR with description

## Commit Messages

Use conventional commits:
- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation
- `test:` Tests
- `refactor:` Code refactoring


