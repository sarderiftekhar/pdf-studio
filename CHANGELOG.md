# Changelog

All notable changes to this project will be documented in this file.

## [1.1.0] - 2026-03-06

### Fixed
- **Bug 1 (Critical)** — `PdfBuilder` was registered as a singleton, causing per-request state (`data`, `driver`, `format`, `landscape`, `margins`, `headerHtml`, `footerHtml`) to leak across renders. Changed to `bind` and added explicit context reset in `view()` and `html()`.
- **Bug 3 (High)** — `WkhtmlDriver` passed raw HTML strings to `--header-html` / `--footer-html` flags which expect file paths. Fixed to write HTML to temporary files and clean up after rendering.
- **Bug 5 (Medium)** — `CssCache::flush()` called `store->clear()` which wiped the entire cache store. Fixed to use a key registry pattern so only PDF Studio CSS entries are removed.
- **Bug 6 (Medium)** — Async `RenderPdfJob` never updated the `RenderJob` record on failure, leaving it permanently in `pending` status. Fixed with try/catch that marks the record `completed` or `failed` appropriately.
- **Bug 7 (Minor)** — Blade directives returned raw HTML strings instead of PHP expression strings, violating the `Blade::directive()` API contract. Fixed all 9 directives to return `<?php echo '...'; ?>` expressions.
- **Bug 8 (Minor)** — API `RenderController` accepted arbitrary view names, allowing any holder of a valid API key to render internal app views. Fixed with an allowlist check against `pdf-studio.api.allowed_views` config and registered template views.

### Changed
- Package renamed from `pdfstudio/laravel` to `sarder/pdfstudio`.

## [1.0.0] - 2026-03-06

### Added
- **Core Rendering Engine** — Fluent API via `Pdf::view()`, `Pdf::html()`, with download, stream, and save output methods
- **Multi-Driver Support** — Chromium (Browsershot), dompdf, and wkhtmltopdf drivers with automatic capability validation
- **Tailwind CSS Pipeline** — Automatic Tailwind v4 compilation with SHA-256 caching
- **Template Registry** — Named templates with default options, data providers, and config-based registration
- **Starter Templates** — Invoice, report, and certificate templates (opt-in)
- **Preview Routes** — Browser-based template preview with HTML/PDF output and environment gating
- **Debug Recorder** — Dumps compiled HTML, CSS, and metadata artifacts to storage
- **Render Events** — `RenderStarting`, `RenderCompleted`, `RenderFailed` lifecycle events
- **Queue Integration** — `RenderPdfJob` for async generation with `Pdf::batch()` for bulk rendering
- **Structured Logging** — Optional render lifecycle logging via configurable channel
- **Blade Directives** — `@pageBreak`, `@pageBreakBefore`, `@avoidBreak` / `@endAvoidBreak`
- **Artisan Commands** — `pdf-studio:cache-clear`, `pdf-studio:templates`
- **Architecture Tests** — Pest architecture tests enforcing contracts, DTOs, and conventions
