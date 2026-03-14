# v3 Market Gap, Research, and Implementation Roadmap

## Goal

Turn PDF Studio from a broad Laravel PDF package into a stronger market contender by closing the biggest gaps against modern alternatives:

- more renderer options
- better accessibility and compliance support
- better font and RTL handling
- stronger testing ergonomics
- stronger large-document and print-layout reliability
- clearer modular packaging and docs

This plan combines product comparison, current market direction, and recurring pain points found in Reddit, Stack Overflow, and upstream issue trackers.

## Executive Summary

PDF Studio is already broader than common Laravel wrappers such as `barryvdh/laravel-dompdf`, `barryvdh/laravel-snappy`, and `spatie/laravel-pdf` in total feature count. The main strategic gap is not breadth. It is alignment with where teams are spending money and engineering time now:

- remote and managed renderers
- accessibility and archival formats
- font and Unicode reliability
- reproducible rendering across Docker, CI, staging, and production
- layout stability for dynamic content
- better developer tooling for diagnosis and testing

The highest-leverage roadmap is:

1. add `weasyprint`, `gotenberg`, and `cloudflare` drivers
2. expose more Chromium and print options directly
3. add PDF metadata, attachments, PDF/A, and PDF/UA-related support
4. build first-class font, Unicode, and RTL support
5. improve test fakes and visual/text assertions
6. add split/flatten/embed-file/document-processing features
7. reorganize docs and package narrative into core vs platform

## Market Comparison

### Current competitors

- `barryvdh/laravel-dompdf`
  - simple and popular
  - low operational complexity
  - weak CSS fidelity and modern layout support
- `barryvdh/laravel-snappy`
  - thin wrapper around `wkhtmltopdf`
  - still used for speed and legacy compatibility
  - held back by old rendering engine
- `spatie/laravel-pdf`
  - strongest current comparison point
  - cleaner modern API
  - multiple renderer backends including hosted and remote paths
  - stronger market perception around developer experience

### Where PDF Studio is already ahead

- template registry
- visual builder/schema flow
- preview endpoints
- SaaS API and workspace concepts
- analytics and usage metering
- watermark/protect/merge/AcroForm support
- thumbnail generation
- render caching

### Where PDF Studio is behind

- no remote/offloaded rendering drivers
- no first-class accessibility/compliance story
- no strong font and RTL subsystem
- no strong “debuggability under real-world rendering drift” story
- no rich PDF testing assertions
- no explicit large-document performance strategy

## Research Signals

### Competitor and platform signals

- Spatie’s docs show a multi-driver strategy that now includes Browsershot, Gotenberg, Cloudflare Browser Rendering, WeasyPrint, and DOMPDF.
  - https://spatie.be/docs/laravel-pdf/v2/introduction
- Puppeteer’s current PDF options expose `outline`, `tagged`, `preferCSSPageSize`, `pageRanges`, `waitForFonts`, and more.
  - https://pptr.dev/api/puppeteer.pdfoptions
- Cloudflare offers Browser Rendering-based PDF generation.
  - https://developers.cloudflare.com/browser-rendering/how-to/pdf-generation/
- Gotenberg exposes a broad document-processing surface and is becoming a common self-hosted PDF service choice.
  - https://gotenberg.dev/docs/getting-started/introduction
  - https://gotenberg.dev/docs/configuration
- WeasyPrint exposes a print-native feature set including PDF forms, attachments, metadata, and PDF variants.
  - https://doc.courtbouillon.org/weasyprint/v66.0/common_use_cases.html
- wkhtmltopdf’s own status page confirms it is effectively legacy tech.
  - https://wkhtmltopdf.org/status

## Problems Repeatedly Reported Online

These are not abstract ideas. They show up repeatedly in public user discussions and upstream issue trackers.

### 1. Fonts, Unicode, and Docker drift

Common complaints:

- custom fonts not loading in Puppeteer/Chromium
- fonts render differently across machines or containers
- Unicode and Acrobat font embedding issues

Examples:

- Reddit: custom fonts not loading with Puppeteer
  - https://www.reddit.com/r/node/comments/iindxd
- Reddit: fonts missing in Puppeteer-generated PDFs in app environments
  - https://www.reddit.com/r/developersIndia/comments/1hm1zsh
- Puppeteer issue: webfonts not reliably loaded before PDF generation
  - https://github.com/puppeteer/puppeteer/issues/422
- Puppeteer issue: custom fonts not loaded when PDF generated
  - https://github.com/puppeteer/puppeteer/issues/3183
- Puppeteer issue: Unicode fonts missing in Acrobat
  - https://github.com/puppeteer/puppeteer/issues/3668
- Puppeteer issue: fonts render differently in Docker
  - https://github.com/puppeteer/puppeteer/issues/2230

What to build:

- a package-managed font registry
- font preflight diagnostics
- explicit `waitForFonts` support
- bundled docs and presets for Docker font packages
- PDF smoke tests for Arabic, CJK, ligatures, emoji, and mixed scripts

### 2. Page breaks, repeated headers, and dynamic layout instability

Common complaints:

- blank pages
- broken page breaks with dynamic content
- table headers not repeating consistently
- headers/footers not appearing or rendering incorrectly
- page numbers and TOC not aligning with actual output

Examples:

- Reddit: dynamic page breaks and running header/footer pain with Chrome/Gotenberg
  - https://www.reddit.com/r/webdev/comments/1m67e80/html_to_pdf_is_such_a_pain_in_the_ass/
- Reddit: PDFs with excessive blank pages and broken pagination
  - https://www.reddit.com/r/webdev/comments/1l32id8
- Stack Overflow: repeated complaints around HTML-to-PDF pagination and layout breakage
  - https://stackoverflow.com/search?q=html+to+pdf+page+break+issues
- Puppeteer issue: header/footer rendering incorrect
  - https://github.com/puppeteer/puppeteer/issues/10024
- Puppeteer issue: `table-header-group` ignored in PDF output
  - https://github.com/puppeteer/puppeteer/issues/10020
- Puppeteer issue: image in header/footer rendering problems
  - https://github.com/puppeteer/puppeteer/issues/2443
- Puppeteer issue: header/footer background styling limitations
  - https://github.com/puppeteer/puppeteer/issues/2182

What to build:

- stronger print-layout helpers beyond the current Blade directives
- page-break debugging mode with visual boundary overlays
- repeated-table-header fallback strategies
- header/footer capability matrix per driver
- TOC and page-number validation tests against real render outputs

### 3. Images and asset loading failures

Common complaints:

- images load in browser but not in PDF
- remote assets fail under dompdf due to `chroot`, remote settings, TLS, or format support
- background images silently fail

Examples:

- Reddit: dompdf image loading frustration and performance complaints
  - https://www.reddit.com/r/laravel/comments/sy4xlw
- Reddit: dompdf image path issue where browser succeeded but PDF failed
  - https://www.reddit.com/r/laravel/comments/ohflt8
- Stack Overflow: dompdf remote image not displaying, with `chroot` and remote asset constraints
  - https://stackoverflow.com/questions/15153139/dompdf-remote-image-is-not-displaying-in-pdf
- Stack Overflow: dompdf images fail due to `chroot`
  - https://stackoverflow.com/questions/69821010/dompdf-image-does-not-show-up
- Stack Overflow: background images not showing in dompdf
  - https://stackoverflow.com/questions/61322615/background-image-is-not-showing-in-dompdf-laravel

What to build:

- asset resolution abstraction
- local-file, signed-URL, inline-base64, and downloaded-temp-file asset strategies
- `assetAudit()` diagnostics step
- unsupported asset format warnings
- remote asset allowlist and security controls

### 4. Large documents, charts, heavy images, and timeouts

Common complaints:

- large PDFs hang or time out
- chart canvases do not render
- browser or canvas height limits cause failures
- multipage documents are too slow or memory-heavy

Examples:

- Reddit: browser canvas-height limits and severe perf cost when converting long pages
  - https://www.reddit.com/r/webdev/comments/1g43hb9
- Reddit: graphs make PDF generation painful
  - https://www.reddit.com/r/laravel/comments/17dc37b
- Reddit: ApexCharts not rendering in Browsershot
  - https://www.reddit.com/r/laravel/comments/kmlc7i
- Puppeteer issue: large-image PDF generation hangs
  - https://github.com/puppeteer/puppeteer/issues/1534

What to build:

- render readiness hooks for JS-heavy pages
- `waitForSelector`, `waitForNetworkIdle`, `waitForJS`, and chart stabilization helpers
- large-document mode with segmentation and merging
- image downscaling/compression pipeline
- performance diagnostics and per-page timing

### 5. Accessibility, archival, and compliance gaps

Common complaints:

- generated PDFs are visually correct but poor for screen readers or archival requirements
- teams need tagged PDFs, outlines, metadata, or PDF/A or PDF/UA variants

Signals:

- Puppeteer exposes `tagged` and `outline`
  - https://pptr.dev/api/puppeteer.pdfoptions
- WeasyPrint documents PDF forms, attachments, metadata, and PDF variants
  - https://doc.courtbouillon.org/weasyprint/v66.0/common_use_cases.html
- Gotenberg documents PDF/A, PDF/UA, metadata, flattening, and related transforms
  - https://gotenberg.dev/docs/configuration

What to build:

- tagged PDF option where the backend supports it
- outline/bookmark generation that maps to driver capabilities
- metadata API
- attachments API
- explicit PDF variant API for archival or accessibility targets

### 6. Environment complexity and operational pain

Common complaints:

- Chromium and Puppeteer are fiddly in Docker or production
- teams want “works without Chrome or Node”
- people move between dompdf, wkhtmltopdf, Browsershot, and hosted services based on ops pain rather than API preference

Examples:

- Reddit: Laravel users struggling with Docker, Chromium, Sail, and package swaps
  - https://www.reddit.com/r/laravel/comments/17dc37b
- Reddit: developers comparing DomPDF vs Browsershot and calling out production setup friction
  - https://www.reddit.com/r/laravel/comments/1qa6hgh
- Reddit: recent Laravel discussion showing appetite for simpler operational models and Gotenberg
  - https://www.reddit.com/r/laravel/comments/1r0mcfl

What to build:

- remote/offloaded renderers
- doctor checks for Chromium, fonts, Node, TLS, temp storage, and memory
- deployment guides for Docker, Forge, Vapor, Herd, and common Linux distros

## Product Strategy

Split the product mentally and in docs into two layers:

### Core

- fluent builder API
- render pipeline
- driver system
- manipulation
- thumbnails
- testing tools

### Platform

- preview routes
- template registry and builder
- versioning
- workspaces
- API rendering
- analytics and metering

Do not necessarily split packages yet, but document and architect toward separable modules.

## v3 Scope

### P0

- remote drivers: Gotenberg, Cloudflare, WeasyPrint
- expanded Chromium PDF options
- font registry and diagnostics
- stronger asset handling and security
- richer test fake and assertions
- driver capability matrix docs

### P1

- metadata, attachments, and PDF variant APIs
- large-document mode and merge pipeline
- JS readiness and chart rendering hooks
- stronger print-layout debugging tools
- RTL presets and reference templates

### P2

- split, flatten, embed-file, extract-metadata features
- optional OCR or searchable-PDF workflow integration
- optional renderer recommendation engine in `doctor`

## Architecture Plan

### 1. Driver subsystem refactor

Create a clearer driver contract split:

- `RendererContract`
- `RemoteRendererContract` for service-backed renderers
- `DriverCapabilities`
- `DriverHealthCheckContract`

Add a richer capabilities model:

- `headerFooterMode`: none, html-template, css-running, remote-service
- `supportsTaggedPdf`
- `supportsOutline`
- `supportsPdfVariants`
- `supportsAttachments`
- `supportsMetadata`
- `supportsJavascript`
- `supportsWaitForFonts`
- `supportsPageRanges`
- `supportsRemoteAssets`

Files likely affected:

- `src/Contracts/RendererContract.php`
- `src/DTOs/DriverCapabilities.php`
- `src/Drivers/DriverManager.php`
- all concrete drivers

### 2. Render options expansion

Add new `RenderOptions` fields:

- `pageRanges`
- `preferCssPageSize`
- `scale`
- `waitForFonts`
- `waitUntil`
- `waitForSelector`
- `waitForFunction`
- `taggedPdf`
- `outline`
- `metadata`
- `attachments`
- `pdfVariant`
- `timeout`
- `networkPolicy`
- `assetPolicy`
- `locale`
- `rtl`
- `fontPreset`

Update:

- `src/DTOs/RenderOptions.php`
- `src/PdfBuilder.php`
- config defaults
- driver capability validation

### 3. Font and asset subsystem

Introduce dedicated services:

- `FontRegistry`
- `FontPreflight`
- `AssetResolver`
- `AssetDownloader`
- `AssetPolicy`

Features:

- register local fonts
- register remote fonts safely
- inline fonts selectively
- validate font availability before render
- normalize asset URLs
- fail fast with actionable diagnostics

### 4. Diagnostics and observability

Extend `doctor` and debug output:

- driver health checks
- Chrome/Node/bin version checks
- font package checks
- image and asset reachability checks
- TLS and certificate warnings
- page-break overlay debug mode
- driver-specific recommendations

### 5. Testing subsystem

Expand fake and assertions:

- `assertRendered`
- `assertDownloaded`
- `assertSaved`
- `assertDriverUsed`
- `assertContainsText`
- `assertContainsHtml`
- `assertPageCount`
- `assertHasMetadata`
- `assertUsesFont`

Optional add-ons:

- text extraction adapter
- thumbnail diff snapshots
- fixture PDFs for regression tests

## Implementation Phases

## Phase 1: Driver Platform Expansion

### Deliverables

- `GotenbergDriver`
- `CloudflareDriver`
- `WeasyPrintDriver`
- config blocks for each
- driver capability matrix
- integration test doubles

### Tasks

1. Add config sections under `pdf-studio.drivers`.
2. Add HTTP clients and request builders for remote drivers.
3. Model driver-specific auth and endpoint configuration.
4. Add `supports()` coverage for each driver.
5. Add tests for request formation and error mapping.
6. Add docs and examples.

### Acceptance Criteria

- user can switch drivers with the same fluent API
- remote-driver errors map cleanly to package exceptions
- docs clearly explain cost, security, and deployment tradeoffs

## Phase 2: Chromium and Print Option Parity

### Deliverables

- new builder methods
- new render options
- capability validation updates

### Tasks

1. Add builder APIs:
   - `pageRanges()`
   - `preferCssPageSize()`
   - `scale()`
   - `waitForFonts()`
   - `waitForSelector()`
   - `waitForFunction()`
   - `taggedPdf()`
   - `outline()`
2. Wire options into Chromium driver.
3. Ignore or warn for unsupported drivers.
4. Add feature tests using fake and real drivers where practical.

### Acceptance Criteria

- Chromium driver exposes a modern print feature set close to Puppeteer
- unsupported drivers warn clearly instead of silently failing

## Phase 3: Accessibility, Metadata, and Document Variants

### Deliverables

- metadata API
- attachments API
- PDF variant API
- bookmarks and outlines normalization

### Tasks

1. Add builder APIs:
   - `metadata(array $metadata)`
   - `attachFile(...)`
   - `pdfVariant(string $variant)`
2. Map features to WeasyPrint and Gotenberg first.
3. Add capability checks and docs.
4. Add tests around DTOs and request formation.

### Acceptance Criteria

- package has a credible compliance story
- docs explain which drivers support which standards

## Phase 4: Fonts, RTL, and Unicode Reliability

### Deliverables

- font registry
- font diagnostics
- font install or publish command
- RTL presets and samples

### Tasks

1. Add `FontRegistry` and config section.
2. Add `pdf-studio:doctor --fonts`.
3. Add sample templates for Arabic, Hebrew, CJK, emoji.
4. Add docs for Docker font packages and fallback chains.
5. Add tests for config and render flows.

### Acceptance Criteria

- users can register and diagnose fonts deliberately
- docs contain copy-pasteable environment setup for common platforms

## Phase 5: Asset Resolution and Security Hardening

### Deliverables

- asset resolver
- remote asset allowlist
- network policy support
- inline asset helpers

### Tasks

1. Add asset policies:
   - local-only
   - allowlist
   - unrestricted
2. Add remote asset download and temp storage support.
3. Add asset audit output to debug recorder.
4. Add request validation for API-side HTML rendering.

### Acceptance Criteria

- image and font failures become diagnosable
- security defaults are explicit and documented

## Phase 6: Large Document and Dynamic Content Support

### Deliverables

- render readiness hooks
- large-document mode
- segmentation and merge strategy
- chart rendering guidance

### Tasks

1. Add JS readiness options and helper methods.
2. Add optional pre-render script support.
3. Add segmentation helper for long reports.
4. Add image compression/downscaling utility.
5. Add benchmark fixtures for large-image and chart-heavy documents.

### Acceptance Criteria

- long documents are less likely to hang or exhaust memory
- chart-heavy renders can wait for readiness deterministically

## Phase 7: Testing and Developer Experience

### Deliverables

- richer `PdfFake`
- text extraction assertion adapter
- visual snapshot helpers
- stronger docs examples

### Tasks

1. Expand `PdfFake` API.
2. Add optional text extractor integration.
3. Add thumbnail snapshot test helpers.
4. Add example test recipes in docs.

### Acceptance Criteria

- package becomes easier to adopt in CI
- users can verify content, not only “a PDF file exists”

## Phase 8: Package Narrative, Docs, and Release Positioning

### Deliverables

- docs split into core and platform
- driver comparison page
- deployment matrix
- migration and recommendation guides

### Tasks

1. Rewrite README into capability-first format.
2. Add “Which driver should I use?” guide.
3. Add troubleshooting guide driven by real-world issue categories.
4. Add example architectures:
   - local Chromium
   - remote Gotenberg
   - Cloudflare
   - WeasyPrint for print-native docs

### Acceptance Criteria

- users can select a driver based on needs instead of trial and error
- operational expectations are obvious before install

## Backlog of Concrete Features to Add

### Rendering and driver features

- Gotenberg driver
- Cloudflare driver
- WeasyPrint driver
- richer Chromium options
- remote render auth support
- per-driver health checks

### Layout and PDF semantics

- tagged PDF
- outline/bookmarks
- page ranges
- CSS page size preference
- metadata
- attachments
- PDF/A and PDF/UA variants

### Fonts and i18n

- font registry
- font fallback presets
- RTL presets
- Unicode diagnostics
- Docker font guidance

### Assets and security

- asset resolver
- remote asset allowlist
- no-network mode
- temp download strategy
- TLS diagnostics

### Performance and stability

- wait hooks for JS/chart readiness
- long-document segmentation
- image optimization
- benchmark suite

### Testing and debugging

- richer fake assertions
- text extraction assertions
- visual snapshot assertions
- page-break overlay mode
- asset audit logs

### Platform improvements

- stronger API request validation
- audit logs for API renders
- per-workspace driver policies
- rate-limit docs and examples

## Suggested Order of Work

### Sprint 1

- richer `RenderOptions`
- Chromium option parity
- driver capability expansion
- docs page for driver matrix

### Sprint 2

- Gotenberg driver
- WeasyPrint driver
- basic metadata and attachment DTOs

### Sprint 3

- Cloudflare driver
- font registry
- doctor font checks

### Sprint 4

- asset resolver and policy controls
- JS readiness hooks
- chart and large-document benchmark fixtures

### Sprint 5

- richer testing API
- text extraction helpers
- troubleshooting docs rewrite

### Sprint 6

- PDF variants
- flatten/split/embed-file work
- platform hardening and audit trails

## Risks

- remote-driver APIs may evolve and require versioned adapters
- accessibility claims must be driver-specific and not oversold
- PDF/A and PDF/UA support can become support-heavy if not tested against validators
- font packaging differs significantly across OS and container bases
- large-document benchmarks can consume CI time and cost if not isolated

## Non-Goals for v3

- building a proprietary rendering engine
- claiming full standards compliance without external validation
- making all drivers behave identically
- supporting every browser-only print quirk on every backend

## Release Recommendation

Position v3 around reliability and deployment flexibility, not just more features.

Suggested release themes:

- “Choose the right renderer for each workload”
- “Production-grade PDF generation for Laravel”
- “Better fonts, better diagnostics, better accessibility”

## References

- Spatie Laravel PDF: https://spatie.be/docs/laravel-pdf/v2/introduction
- Puppeteer PDF options: https://pptr.dev/api/puppeteer.pdfoptions
- Cloudflare Browser Rendering PDF generation: https://developers.cloudflare.com/browser-rendering/how-to/pdf-generation/
- Gotenberg routes: https://gotenberg.dev/docs/routes
- Gotenberg configuration: https://gotenberg.dev/docs/configuration
- Gotenberg introduction: https://gotenberg.dev/docs/getting-started/introduction
- WeasyPrint common use cases: https://doc.courtbouillon.org/weasyprint/v66.0/common_use_cases.html
- wkhtmltopdf status: https://wkhtmltopdf.org/status
- Reddit, Laravel PDF generation pain: https://www.reddit.com/r/laravel/comments/17dc37b
- Reddit, HTML to PDF pain and Gotenberg discussion: https://www.reddit.com/r/webdev/comments/1m67e80/html_to_pdf_is_such_a_pain_in_the_ass/
- Reddit, HTML to PDF perf and canvas limits: https://www.reddit.com/r/webdev/comments/1g43hb9
- Reddit, DomPDF vs Browsershot discussion: https://www.reddit.com/r/laravel/comments/1qa6hgh
- Reddit, recent Laravel PDF tool discussion: https://www.reddit.com/r/laravel/comments/1r0mcfl
- Reddit, Puppeteer font-loading issue: https://www.reddit.com/r/node/comments/iindxd
- Reddit, Puppeteer fonts missing in Next/Node app: https://www.reddit.com/r/developersIndia/comments/1hm1zsh
- Reddit, Browsershot chart rendering issue: https://www.reddit.com/r/laravel/comments/kmlc7i
- Stack Overflow, dompdf remote image issue: https://stackoverflow.com/questions/15153139/dompdf-remote-image-is-not-displaying-in-pdf
- Stack Overflow, dompdf `chroot` image issue: https://stackoverflow.com/questions/69821010/dompdf-image-does-not-show-up
- Stack Overflow, dompdf background image issue: https://stackoverflow.com/questions/61322615/background-image-is-not-showing-in-dompdf-laravel
- Puppeteer issue, header/footer bug: https://github.com/puppeteer/puppeteer/issues/10024
- Puppeteer issue, repeated table header issue: https://github.com/puppeteer/puppeteer/issues/10020
- Puppeteer issue, image in header/footer issue: https://github.com/puppeteer/puppeteer/issues/2443
- Puppeteer issue, footer background limitation: https://github.com/puppeteer/puppeteer/issues/2182
- Puppeteer issue, webfonts PDF timing issue: https://github.com/puppeteer/puppeteer/issues/422
- Puppeteer issue, custom fonts not loaded: https://github.com/puppeteer/puppeteer/issues/3183
- Puppeteer issue, Unicode/Acrobat font issue: https://github.com/puppeteer/puppeteer/issues/3668
- Puppeteer issue, Docker fonts drift: https://github.com/puppeteer/puppeteer/issues/2230
- Puppeteer issue, large-image PDF hang: https://github.com/puppeteer/puppeteer/issues/1534
