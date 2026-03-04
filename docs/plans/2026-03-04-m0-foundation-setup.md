# M0: Foundation Setup — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Bootstrap the `pdfstudio/laravel` package with a working service provider, config, facade, fluent API stub, CI pipeline, and contribution docs — ready for M1 core rendering work.

**Architecture:** Laravel package with auto-discovery. Service provider registers config and facade. PdfBuilder is the fluent entry point. Pipeline and driver contracts are stubbed so M1 has stable interfaces to implement against.

**Tech Stack:** PHP 8.1+, Laravel 10+, Pest PHP, PHPStan, Laravel Pint, GitHub Actions

---

### Task 1: Initialize Git Repository

**Files:**
- Create: `.gitignore`

**Step 1: Initialize repo**

```bash
cd /Users/sarderiftekhar/Herd/pdf-studio
git init
```

**Step 2: Create .gitignore**

Create `.gitignore`:

```
/vendor/
/node_modules/
/.idea/
/.vscode/
*.cache
.phpunit.result.cache
.php-cs-fixer.cache
composer.lock
.DS_Store
/coverage/
/build/
/.phpunit.cache/
```

**Step 3: Commit**

```bash
git add .gitignore docs/ github-milestones-issues.md pdf_studio_prd.docx scripts/
git commit -m "chore: initial project files with PRD and design docs"
```

---

### Task 2: Create composer.json

**Files:**
- Create: `composer.json`

**Step 1: Create composer.json**

```json
{
    "name": "pdfstudio/laravel",
    "description": "Design, preview, and generate PDFs using HTML and TailwindCSS in Laravel",
    "keywords": ["laravel", "pdf", "tailwind", "html-to-pdf", "chromium", "dompdf"],
    "license": "MIT",
    "type": "library",
    "authors": [
        {
            "name": "Sarder Iftekhar",
            "email": "sarder@example.com"
        }
    ],
    "require": {
        "php": "^8.1",
        "illuminate/contracts": "^10.0|^11.0|^12.0",
        "illuminate/support": "^10.0|^11.0|^12.0"
    },
    "require-dev": {
        "orchestra/testbench": "^8.0|^9.0|^10.0",
        "pestphp/pest": "^2.0|^3.0",
        "pestphp/pest-plugin-laravel": "^2.0|^3.0",
        "pestphp/pest-plugin-arch": "^2.0|^3.0",
        "phpstan/phpstan": "^1.10",
        "laravel/pint": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "PdfStudio\\Laravel\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "PdfStudio\\Laravel\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "PdfStudio\\Laravel\\PdfStudioServiceProvider"
            ],
            "aliases": {
                "Pdf": "PdfStudio\\Laravel\\Facades\\Pdf"
            }
        }
    },
    "config": {
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true,
    "scripts": {
        "test": "pest",
        "test:coverage": "pest --coverage",
        "lint": "pint",
        "analyse": "phpstan analyse"
    }
}
```

**Step 2: Install dependencies**

```bash
composer install
```

**Step 3: Commit**

```bash
git add composer.json
git commit -m "chore: add composer.json with package metadata and dev dependencies"
```

---

### Task 3: Create Config File

**Files:**
- Create: `config/pdf-studio.php`

**Step 1: Create config file**

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Renderer Driver
    |--------------------------------------------------------------------------
    |
    | The default driver used for PDF rendering. Supported: "chromium",
    | "wkhtmltopdf", "dompdf".
    |
    */
    'default_driver' => env('PDF_STUDIO_DRIVER', 'chromium'),

    /*
    |--------------------------------------------------------------------------
    | Renderer Drivers
    |--------------------------------------------------------------------------
    |
    | Configuration for each supported rendering engine.
    |
    */
    'drivers' => [
        'chromium' => [
            'binary' => null,
            'node_binary' => null,
            'npm_binary' => null,
            'timeout' => 60,
            'options' => [],
        ],
        'wkhtmltopdf' => [
            'binary' => '/usr/local/bin/wkhtmltopdf',
            'timeout' => 60,
        ],
        'dompdf' => [
            'paper' => 'A4',
            'orientation' => 'portrait',
            'options' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tailwind CSS
    |--------------------------------------------------------------------------
    |
    | Configuration for the Tailwind CSS compilation pipeline.
    |
    */
    'tailwind' => [
        'binary' => null,
        'config' => null,
        'cache' => [
            'enabled' => true,
            'store' => null,
            'ttl' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Preview
    |--------------------------------------------------------------------------
    |
    | Preview route configuration. Disabled by default.
    |
    */
    'preview' => [
        'enabled' => env('PDF_STUDIO_PREVIEW', false),
        'prefix' => 'pdf-studio/preview',
        'middleware' => ['web', 'auth'],
        'data_providers' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug
    |--------------------------------------------------------------------------
    |
    | When enabled, renders dump HTML/CSS artifacts and timing to storage.
    |
    */
    'debug' => env('PDF_STUDIO_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Output
    |--------------------------------------------------------------------------
    |
    | Default output and storage settings.
    |
    */
    'output' => [
        'default_disk' => null,
        'overwrite' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | Queue settings for async PDF generation.
    |
    */
    'queue' => [
        'connection' => null,
        'queue' => 'default',
        'timeout' => 120,
        'retries' => 3,
    ],

];
```

**Step 2: Commit**

```bash
git add config/
git commit -m "feat: add pdf-studio config file with all driver, tailwind, preview, and queue settings"
```

---

### Task 4: Create Service Provider

**Files:**
- Create: `src/PdfStudioServiceProvider.php`

**Step 1: Create the service provider**

```php
<?php

namespace PdfStudio\Laravel;

use Illuminate\Support\ServiceProvider;

class PdfStudioServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/pdf-studio.php',
            'pdf-studio'
        );

        $this->app->singleton(PdfBuilder::class, function ($app) {
            return new PdfBuilder($app);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/pdf-studio.php' => config_path('pdf-studio.php'),
            ], 'pdf-studio-config');
        }
    }
}
```

**Step 2: Commit**

```bash
git add src/PdfStudioServiceProvider.php
git commit -m "feat: add PdfStudioServiceProvider with config merge and publish"
```

---

### Task 5: Create Facade

**Files:**
- Create: `src/Facades/Pdf.php`

**Step 1: Create the facade**

```php
<?php

namespace PdfStudio\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use PdfStudio\Laravel\PdfBuilder;

/**
 * @method static PdfBuilder view(string $view)
 * @method static PdfBuilder html(string $html)
 *
 * @see PdfBuilder
 */
class Pdf extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PdfBuilder::class;
    }
}
```

**Step 2: Commit**

```bash
git add src/Facades/
git commit -m "feat: add Pdf facade with method annotations"
```

---

### Task 6: Create Contracts

**Files:**
- Create: `src/Contracts/RendererContract.php`
- Create: `src/Contracts/CssCompilerContract.php`

**Step 1: Create RendererContract**

```php
<?php

namespace PdfStudio\Laravel\Contracts;

use PdfStudio\Laravel\DTOs\DriverCapabilities;
use PdfStudio\Laravel\DTOs\RenderOptions;

interface RendererContract
{
    /**
     * Render HTML content to PDF bytes.
     */
    public function render(string $html, RenderOptions $options): string;

    /**
     * Return the capabilities of this driver.
     */
    public function supports(): DriverCapabilities;
}
```

**Step 2: Create CssCompilerContract**

```php
<?php

namespace PdfStudio\Laravel\Contracts;

interface CssCompilerContract
{
    /**
     * Compile CSS for the given HTML content.
     */
    public function compile(string $html): string;
}
```

**Step 3: Commit**

```bash
git add src/Contracts/
git commit -m "feat: add RendererContract and CssCompilerContract interfaces"
```

---

### Task 7: Create DTOs

**Files:**
- Create: `src/DTOs/RenderOptions.php`
- Create: `src/DTOs/RenderContext.php`
- Create: `src/DTOs/DriverCapabilities.php`

**Step 1: Create RenderOptions**

```php
<?php

namespace PdfStudio\Laravel\DTOs;

class RenderOptions
{
    public function __construct(
        public string $format = 'A4',
        public bool $landscape = false,
        public array $margins = ['top' => 10, 'right' => 10, 'bottom' => 10, 'left' => 10],
        public bool $printBackground = true,
        public ?string $headerHtml = null,
        public ?string $footerHtml = null,
    ) {}
}
```

**Step 2: Create RenderContext**

```php
<?php

namespace PdfStudio\Laravel\DTOs;

class RenderContext
{
    public function __construct(
        public ?string $viewName = null,
        public ?string $rawHtml = null,
        public array $data = [],
        public ?string $compiledHtml = null,
        public ?string $compiledCss = null,
        public ?string $styledHtml = null,
        public ?string $pdfContent = null,
        public ?RenderOptions $options = null,
    ) {
        $this->options ??= new RenderOptions();
    }
}
```

**Step 3: Create DriverCapabilities**

```php
<?php

namespace PdfStudio\Laravel\DTOs;

class DriverCapabilities
{
    public function __construct(
        public bool $landscape = true,
        public bool $customMargins = true,
        public bool $headerFooter = false,
        public bool $printBackground = true,
        public array $supportedFormats = ['A4', 'Letter', 'Legal'],
    ) {}
}
```

**Step 4: Commit**

```bash
git add src/DTOs/
git commit -m "feat: add RenderOptions, RenderContext, and DriverCapabilities DTOs"
```

---

### Task 8: Create PdfBuilder (Fluent API Stub)

**Files:**
- Create: `src/PdfBuilder.php`
- Create: `src/Exceptions/RenderException.php`
- Create: `src/Exceptions/DriverException.php`

**Step 1: Create exception classes**

`src/Exceptions/RenderException.php`:

```php
<?php

namespace PdfStudio\Laravel\Exceptions;

use RuntimeException;

class RenderException extends RuntimeException {}
```

`src/Exceptions/DriverException.php`:

```php
<?php

namespace PdfStudio\Laravel\Exceptions;

use RuntimeException;

class DriverException extends RuntimeException {}
```

**Step 2: Create PdfBuilder**

```php
<?php

namespace PdfStudio\Laravel;

use Illuminate\Contracts\Foundation\Application;
use PdfStudio\Laravel\DTOs\RenderContext;
use PdfStudio\Laravel\DTOs\RenderOptions;
use PdfStudio\Laravel\Exceptions\RenderException;

class PdfBuilder
{
    protected RenderContext $context;

    protected ?string $driver = null;

    public function __construct(
        protected Application $app,
    ) {
        $this->context = new RenderContext();
    }

    public function view(string $view): static
    {
        $this->context->viewName = $view;
        $this->context->rawHtml = null;

        return $this;
    }

    public function html(string $html): static
    {
        $this->context->rawHtml = $html;
        $this->context->viewName = null;

        return $this;
    }

    public function data(array $data): static
    {
        $this->context->data = $data;

        return $this;
    }

    public function driver(string $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    public function format(string $format): static
    {
        $this->context->options->format = $format;

        return $this;
    }

    public function landscape(bool $landscape = true): static
    {
        $this->context->options->landscape = $landscape;

        return $this;
    }

    public function margins(
        ?int $top = null,
        ?int $right = null,
        ?int $bottom = null,
        ?int $left = null,
    ): static {
        if ($top !== null) {
            $this->context->options->margins['top'] = $top;
        }
        if ($right !== null) {
            $this->context->options->margins['right'] = $right;
        }
        if ($bottom !== null) {
            $this->context->options->margins['bottom'] = $bottom;
        }
        if ($left !== null) {
            $this->context->options->margins['left'] = $left;
        }

        return $this;
    }

    public function getContext(): RenderContext
    {
        return $this->context;
    }

    public function getDriver(): ?string
    {
        return $this->driver;
    }

    public function getResolvedDriverName(): string
    {
        return $this->driver ?? $this->app['config']->get('pdf-studio.default_driver', 'chromium');
    }
}
```

**Step 3: Commit**

```bash
git add src/PdfBuilder.php src/Exceptions/
git commit -m "feat: add PdfBuilder fluent API stub and exception classes"
```

---

### Task 9: Set Up Pest Testing

**Files:**
- Create: `tests/Pest.php`
- Create: `tests/TestCase.php`
- Create: `phpunit.xml`

**Step 1: Create TestCase base class**

```php
<?php

namespace PdfStudio\Laravel\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use PdfStudio\Laravel\PdfStudioServiceProvider;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PdfStudioServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Pdf' => \PdfStudio\Laravel\Facades\Pdf::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('pdf-studio.default_driver', 'chromium');
    }
}
```

**Step 2: Create Pest.php config**

```php
<?php

use PdfStudio\Laravel\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');
```

**Step 3: Create phpunit.xml**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
    bootstrap="vendor/autoload.php"
    colors="true"
    cacheDirectory=".phpunit.cache"
>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
        <testsuite name="Architecture">
            <directory>tests/Architecture</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

**Step 4: Commit**

```bash
git add tests/Pest.php tests/TestCase.php phpunit.xml
git commit -m "chore: set up Pest testing with Orchestra Testbench"
```

---

### Task 10: Write Architecture Tests

**Files:**
- Create: `tests/Architecture/ArchTest.php`

**Step 1: Write the architecture tests**

```php
<?php

arch()->preset()->php();

arch('contracts are interfaces')
    ->expect('PdfStudio\Laravel\Contracts')
    ->toBeInterfaces();

arch('DTOs have no dependencies on framework')
    ->expect('PdfStudio\Laravel\DTOs')
    ->toUseNothing();

arch('exceptions extend RuntimeException')
    ->expect('PdfStudio\Laravel\Exceptions')
    ->toExtend(RuntimeException::class);
```

**Step 2: Run tests to verify they pass**

```bash
./vendor/bin/pest tests/Architecture/
```

Expected: all architecture tests pass.

**Step 3: Commit**

```bash
git add tests/Architecture/
git commit -m "test: add architecture tests for contracts, DTOs, and exceptions"
```

---

### Task 11: Write Unit Tests for DTOs

**Files:**
- Create: `tests/Unit/DTOs/RenderOptionsTest.php`
- Create: `tests/Unit/DTOs/RenderContextTest.php`
- Create: `tests/Unit/DTOs/DriverCapabilitiesTest.php`

**Step 1: Write RenderOptions tests**

```php
<?php

use PdfStudio\Laravel\DTOs\RenderOptions;

it('has sensible defaults', function () {
    $options = new RenderOptions();

    expect($options->format)->toBe('A4')
        ->and($options->landscape)->toBeFalse()
        ->and($options->margins)->toBe(['top' => 10, 'right' => 10, 'bottom' => 10, 'left' => 10])
        ->and($options->printBackground)->toBeTrue()
        ->and($options->headerHtml)->toBeNull()
        ->and($options->footerHtml)->toBeNull();
});

it('accepts custom values', function () {
    $options = new RenderOptions(
        format: 'Letter',
        landscape: true,
        margins: ['top' => 20, 'right' => 20, 'bottom' => 20, 'left' => 20],
        printBackground: false,
        headerHtml: '<p>Header</p>',
        footerHtml: '<p>Footer</p>',
    );

    expect($options->format)->toBe('Letter')
        ->and($options->landscape)->toBeTrue()
        ->and($options->margins['top'])->toBe(20)
        ->and($options->printBackground)->toBeFalse()
        ->and($options->headerHtml)->toBe('<p>Header</p>')
        ->and($options->footerHtml)->toBe('<p>Footer</p>');
});
```

**Step 2: Write RenderContext tests**

```php
<?php

use PdfStudio\Laravel\DTOs\RenderContext;
use PdfStudio\Laravel\DTOs\RenderOptions;

it('initializes with default RenderOptions', function () {
    $context = new RenderContext();

    expect($context->options)->toBeInstanceOf(RenderOptions::class)
        ->and($context->viewName)->toBeNull()
        ->and($context->rawHtml)->toBeNull()
        ->and($context->data)->toBe([]);
});

it('accepts custom values', function () {
    $context = new RenderContext(
        viewName: 'invoices.show',
        data: ['invoice' => ['id' => 1]],
    );

    expect($context->viewName)->toBe('invoices.show')
        ->and($context->data)->toBe(['invoice' => ['id' => 1]]);
});
```

**Step 3: Write DriverCapabilities tests**

```php
<?php

use PdfStudio\Laravel\DTOs\DriverCapabilities;

it('has sensible defaults', function () {
    $caps = new DriverCapabilities();

    expect($caps->landscape)->toBeTrue()
        ->and($caps->customMargins)->toBeTrue()
        ->and($caps->headerFooter)->toBeFalse()
        ->and($caps->printBackground)->toBeTrue()
        ->and($caps->supportedFormats)->toBe(['A4', 'Letter', 'Legal']);
});
```

**Step 4: Run tests**

```bash
./vendor/bin/pest tests/Unit/DTOs/
```

Expected: all DTO tests pass.

**Step 5: Commit**

```bash
git add tests/Unit/
git commit -m "test: add unit tests for RenderOptions, RenderContext, and DriverCapabilities DTOs"
```

---

### Task 12: Write Feature Tests for Service Provider and Facade

**Files:**
- Create: `tests/Feature/ServiceProviderTest.php`
- Create: `tests/Feature/FacadeTest.php`

**Step 1: Write ServiceProvider tests**

```php
<?php

use PdfStudio\Laravel\PdfBuilder;

it('registers PdfBuilder as a singleton', function () {
    $builder1 = app(PdfBuilder::class);
    $builder2 = app(PdfBuilder::class);

    expect($builder1)->toBeInstanceOf(PdfBuilder::class)
        ->and($builder1)->toBe($builder2);
});

it('merges default config', function () {
    expect(config('pdf-studio.default_driver'))->toBe('chromium')
        ->and(config('pdf-studio.drivers'))->toBeArray()
        ->and(config('pdf-studio.drivers'))->toHaveKeys(['chromium', 'wkhtmltopdf', 'dompdf']);
});
```

**Step 2: Write Facade tests**

```php
<?php

use PdfStudio\Laravel\Facades\Pdf;
use PdfStudio\Laravel\PdfBuilder;

it('resolves to PdfBuilder', function () {
    expect(Pdf::getFacadeRoot())->toBeInstanceOf(PdfBuilder::class);
});
```

**Step 3: Run tests**

```bash
./vendor/bin/pest tests/Feature/
```

Expected: all feature tests pass.

**Step 4: Commit**

```bash
git add tests/Feature/
git commit -m "test: add feature tests for ServiceProvider and Facade"
```

---

### Task 13: Write Feature Tests for PdfBuilder Fluent API

**Files:**
- Create: `tests/Feature/PdfBuilderTest.php`

**Step 1: Write the tests**

```php
<?php

use PdfStudio\Laravel\Facades\Pdf;
use PdfStudio\Laravel\PdfBuilder;

it('sets view name via fluent API', function () {
    $builder = Pdf::view('invoices.show');

    expect($builder)->toBeInstanceOf(PdfBuilder::class)
        ->and($builder->getContext()->viewName)->toBe('invoices.show');
});

it('sets raw HTML via fluent API', function () {
    $builder = Pdf::html('<h1>Test</h1>');

    expect($builder->getContext()->rawHtml)->toBe('<h1>Test</h1>')
        ->and($builder->getContext()->viewName)->toBeNull();
});

it('clears view when html is set and vice versa', function () {
    $builder = Pdf::view('test.view')->html('<h1>Override</h1>');

    expect($builder->getContext()->viewName)->toBeNull()
        ->and($builder->getContext()->rawHtml)->toBe('<h1>Override</h1>');
});

it('chains data correctly', function () {
    $builder = Pdf::view('invoices.show')
        ->data(['invoice' => ['id' => 1]]);

    expect($builder->getContext()->data)->toBe(['invoice' => ['id' => 1]]);
});

it('sets driver override', function () {
    $builder = Pdf::view('test')->driver('dompdf');

    expect($builder->getResolvedDriverName())->toBe('dompdf');
});

it('uses default driver when none specified', function () {
    $builder = Pdf::view('test');

    expect($builder->getResolvedDriverName())->toBe('chromium');
});

it('sets format', function () {
    $builder = Pdf::view('test')->format('Letter');

    expect($builder->getContext()->options->format)->toBe('Letter');
});

it('sets landscape', function () {
    $builder = Pdf::view('test')->landscape();

    expect($builder->getContext()->options->landscape)->toBeTrue();
});

it('sets margins', function () {
    $builder = Pdf::view('test')->margins(top: 20, bottom: 30);

    $margins = $builder->getContext()->options->margins;

    expect($margins['top'])->toBe(20)
        ->and($margins['bottom'])->toBe(30)
        ->and($margins['right'])->toBe(10)
        ->and($margins['left'])->toBe(10);
});

it('supports full method chaining', function () {
    $builder = Pdf::view('invoices.show')
        ->data(['invoice' => ['id' => 1]])
        ->driver('chromium')
        ->format('A4')
        ->landscape()
        ->margins(top: 20, bottom: 20);

    expect($builder->getContext()->viewName)->toBe('invoices.show')
        ->and($builder->getContext()->data)->toBe(['invoice' => ['id' => 1]])
        ->and($builder->getResolvedDriverName())->toBe('chromium')
        ->and($builder->getContext()->options->format)->toBe('A4')
        ->and($builder->getContext()->options->landscape)->toBeTrue()
        ->and($builder->getContext()->options->margins['top'])->toBe(20);
});
```

**Step 2: Run tests**

```bash
./vendor/bin/pest tests/Feature/PdfBuilderTest.php
```

Expected: all tests pass.

**Step 3: Commit**

```bash
git add tests/Feature/PdfBuilderTest.php
git commit -m "test: add comprehensive fluent API tests for PdfBuilder"
```

---

### Task 14: Configure PHPStan

**Files:**
- Create: `phpstan.neon`

**Step 1: Create phpstan config**

```neon
includes:
    - ./vendor/phpstan/phpstan/conf/bleedingEdge.neon

parameters:
    level: 6
    paths:
        - src
    tmpDir: build/phpstan
```

**Step 2: Run static analysis**

```bash
./vendor/bin/phpstan analyse
```

Expected: no errors. If there are errors, fix them before committing.

**Step 3: Commit**

```bash
git add phpstan.neon
git commit -m "chore: configure PHPStan at level 6"
```

---

### Task 15: Configure Laravel Pint

**Files:**
- Create: `pint.json`

**Step 1: Create Pint config**

```json
{
    "preset": "laravel",
    "rules": {
        "concat_space": {
            "spacing": "none"
        },
        "not_operator_with_successor_space": false
    }
}
```

**Step 2: Run Pint to fix any style issues**

```bash
./vendor/bin/pint
```

**Step 3: Commit any formatting changes**

```bash
git add pint.json
git add -A
git commit -m "chore: configure Laravel Pint and fix code style"
```

---

### Task 16: Create GitHub Actions CI

**Files:**
- Create: `.github/workflows/ci.yml`

**Step 1: Create CI workflow**

```yaml
name: CI

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  tests:
    runs-on: ubuntu-latest
    strategy:
      fail-fast: true
      matrix:
        php: ['8.1', '8.2', '8.3']
        laravel: ['10.*', '11.*']
        exclude:
          - php: '8.1'
            laravel: '11.*'

    name: PHP ${{ matrix.php }} / Laravel ${{ matrix.laravel }}

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: dom, curl, libxml, mbstring, zip
          coverage: none

      - name: Install dependencies
        run: |
          composer require "illuminate/support:${{ matrix.laravel }}" --no-interaction --no-update
          composer update --prefer-dist --no-interaction

      - name: Run tests
        run: vendor/bin/pest

  static-analysis:
    runs-on: ubuntu-latest
    name: Static Analysis

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          coverage: none

      - name: Install dependencies
        run: composer update --prefer-dist --no-interaction

      - name: Run PHPStan
        run: vendor/bin/phpstan analyse

  code-style:
    runs-on: ubuntu-latest
    name: Code Style

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          coverage: none

      - name: Install dependencies
        run: composer update --prefer-dist --no-interaction

      - name: Run Pint
        run: vendor/bin/pint --test
```

**Step 2: Commit**

```bash
mkdir -p .github/workflows
git add .github/
git commit -m "ci: add GitHub Actions for tests, static analysis, and code style"
```

---

### Task 17: Create CONTRIBUTING.md and README.md

**Files:**
- Create: `CONTRIBUTING.md`
- Create: `README.md`
- Create: `LICENSE`

**Step 1: Create LICENSE (MIT)**

```
MIT License

Copyright (c) 2026 PDF Studio

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

**Step 2: Create README.md**

```markdown
# PDF Studio for Laravel

Design, preview, and generate PDFs using HTML and TailwindCSS in Laravel.

## Installation

```bash
composer require pdfstudio/laravel
```

## Quick Start

```php
use PdfStudio\Laravel\Facades\Pdf;

// Generate and download a PDF
Pdf::view('invoices.show')
    ->data(['invoice' => $invoice])
    ->download('invoice.pdf');

// Save to storage
Pdf::view('reports.quarterly')
    ->data(['report' => $report])
    ->driver('chromium')
    ->format('A4')
    ->landscape()
    ->save('reports/q1.pdf', 's3');

// Render from inline HTML
Pdf::html('<h1>Hello World</h1>')
    ->render();
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=pdf-studio-config
```

## Supported Drivers

| Driver | Binary Required | CSS Fidelity |
|---|---|---|
| Chromium (default) | Node.js + Puppeteer | Full |
| wkhtmltopdf | wkhtmltopdf binary | Good |
| dompdf | None | Limited |

## Testing

```bash
composer test
```

## License

MIT
```

**Step 3: Create CONTRIBUTING.md**

```markdown
# Contributing to PDF Studio

## Local Development Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/pdfstudio/laravel.git
   cd laravel
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
```

**Step 4: Commit**

```bash
git add LICENSE README.md CONTRIBUTING.md
git commit -m "docs: add README, CONTRIBUTING guide, and MIT license"
```

---

### Task 18: Run Full Test Suite and Verify

**Step 1: Run all tests**

```bash
./vendor/bin/pest
```

Expected: all tests pass across Unit, Feature, and Architecture suites.

**Step 2: Run static analysis**

```bash
./vendor/bin/phpstan analyse
```

Expected: no errors.

**Step 3: Run code style check**

```bash
./vendor/bin/pint --test
```

Expected: no style issues.

**Step 4: Verify package installs cleanly**

Create a temporary Laravel app and require the package from path to verify it installs without errors. (Manual verification step — can be automated later.)

---

## Task Dependency Graph

```
Task 1 (git init)
  └── Task 2 (composer.json)
        └── Task 3 (config)
        └── Task 4 (service provider)
        └── Task 5 (facade)
        └── Task 6 (contracts)
        └── Task 7 (DTOs)
        └── Task 8 (PdfBuilder + exceptions)
        └── Task 9 (Pest setup)
              └── Task 10 (arch tests)
              └── Task 11 (DTO unit tests)
              └── Task 12 (SP + facade feature tests)
              └── Task 13 (PdfBuilder feature tests)
        └── Task 14 (PHPStan)
        └── Task 15 (Pint)
        └── Task 16 (GitHub Actions)
        └── Task 17 (docs)
              └── Task 18 (full verification)
```

## Summary

18 tasks, each 2-5 minutes. After completion, the package will:

- Install in a fresh Laravel 10/11 app without errors
- Publish config via `php artisan vendor:publish`
- Provide a working fluent API stub (`Pdf::view()->data()->...`)
- Have stable contracts for M1 to implement against
- Pass CI with tests, static analysis, and code style checks
- Have contribution docs for external developers
