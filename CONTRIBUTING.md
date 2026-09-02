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

## Continuous Integration

Every pull request runs three workflows on GitHub Actions:

- **Tests** — Pest across a matrix of PHP 8.2, 8.3, 8.4 and Laravel 11, 12, 13 (Laravel 13 requires PHP 8.3+, so that combination is excluded)
- **Static Analysis** — PHPStan on PHP 8.3
- **Code Style** — Pint on PHP 8.3

`composer.lock` is gitignored, so CI resolves dependencies fresh on every run. If a job fails at the "Install dependencies" step, a newly released dependency or Composer version is usually the cause rather than the code change.

The Laravel 11 matrix jobs disable Composer's `policy.advisories.block` setting before installing. Composer 2.10+ refuses to install packages with open security advisories, and Laravel 11 is past its security-support window, so its releases can no longer be resolved under the default policy. Laravel 12 and 13 resolve normally.

## Dependency Updates

Dependabot is configured in `.github/dependabot.yml`:

- **Composer** — weekly, minor and patch updates grouped into one PR
- **npm** (Puppeteer for the Chromium driver) — weekly
- **GitHub Actions** — monthly

To check manually:

```bash
composer outdated --direct   # direct Composer dependencies
composer audit               # security advisories
npm outdated                 # Puppeteer
npm audit fix                # patch npm advisories within the current semver range
```

`package-lock.json` is committed, so npm updates must be applied locally and included in the PR. Puppeteer major upgrades need a Browsershot compatibility check before they are merged.

## Release Process

Releases follow semantic versioning. Tags are cut from the `main` branch after CI passes.
