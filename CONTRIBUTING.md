# Contributing to PDF Studio

## Local Development Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/sarderiftekhar/pdf-studio.git
   cd pdf-studio
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Run tests:
   ```bash
   composer test
   ```

## Code Style

This project uses [Laravel Pint](https://laravel.com/docs/pint) with the Laravel preset.

```bash
composer lint
```

## Static Analysis

PHPStan is configured at level 6:

```bash
composer analyse
```

## Testing

We use [Pest PHP](https://pestphp.com/) for testing.

- **Unit tests** go in `tests/Unit/` — test individual classes in isolation
- **Feature tests** go in `tests/Feature/` — test package integration with Laravel
- **Architecture tests** go in `tests/Architecture/` — enforce structural rules

```bash
# Run all tests
composer test

# Run specific suite
./vendor/bin/pest tests/Unit
./vendor/bin/pest tests/Feature
```

## Pull Request Process

1. Fork the repository and create a feature branch
2. Write tests for new functionality (TDD preferred)
3. Ensure all tests pass: `composer test`
4. Ensure code style passes: `composer lint`
5. Ensure static analysis passes: `composer analyse`
6. Submit a pull request with a clear description

## Commit Message Convention

Use conventional commits:

- `feat:` new feature
- `fix:` bug fix
- `test:` adding or updating tests
- `chore:` maintenance tasks
- `ci:` CI/CD changes
- `docs:` documentation

## Release Process

Releases follow semantic versioning. Tags are cut from the `main` branch after CI passes.
