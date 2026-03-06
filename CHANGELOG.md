# Changelog

All notable changes to this project will be documented in this file.

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
