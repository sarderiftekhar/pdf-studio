# PDF Studio for Laravel: GitHub Milestones and Issues

## Labels
- `type:epic`
- `type:feature`
- `type:bug`
- `type:chore`
- `type:docs`
- `area:core`
- `area:driver`
- `area:tailwind`
- `area:preview`
- `area:security`
- `area:queue`
- `area:pro`
- `area:saas`
- `priority:p0`
- `priority:p1`
- `priority:p2`

## Milestone M0 - Foundation Setup (Week 1)
### Epic: Package Foundation and CI (`type:epic`, `priority:p0`, `area:core`)
Issue 1: Bootstrap Laravel package skeleton (`type:feature`, `priority:p0`, `area:core`)
- Scope: service provider, config publish, facade/helper entrypoint, basic docs scaffold.
- Acceptance criteria:
- Package installs in a fresh Laravel app without errors.
- `php artisan vendor:publish` publishes package config.
- Basic example usage runs in local smoke test.

Issue 2: Configure CI and quality gates (`type:chore`, `priority:p0`, `area:core`)
- Scope: GitHub Actions for lint, static analysis, unit tests on supported PHP/Laravel matrix.
- Acceptance criteria:
- CI runs on push and PR.
- Required checks block merge when failing.
- Build matrix covers all supported versions.

Issue 3: Establish coding conventions and contribution docs (`type:docs`, `priority:p1`, `area:core`)
- Scope: `CONTRIBUTING.md`, code style rules, release process notes.
- Acceptance criteria:
- Contribution flow documented end-to-end.
- Local dev setup steps verified on clean machine.

## Milestone M1 - Core Rendering Engine (Weeks 2-3)
### Epic: Unified PDF API (`type:epic`, `priority:p0`, `area:core`)
Issue 4: Define renderer contract and request DTOs (`type:feature`, `priority:p0`, `area:core`)
- Scope: stable interfaces for renderer drivers and render options.
- Acceptance criteria:
- Contract supports view, data payload, output mode, and options.
- Contract is documented and tested with a fake driver.

Issue 5: Implement fluent API (`type:feature`, `priority:p0`, `area:core`)
- Scope: `Pdf::make()->view()->data()->download()/save()/stream()/render()`.
- Acceptance criteria:
- All fluent methods chain correctly.
- Invalid options return actionable exceptions.
- API example in docs matches runtime behavior.

Issue 6: File output and storage support (`type:feature`, `priority:p1`, `area:core`)
- Scope: local and Laravel storage disk writing, overwrite behavior flags.
- Acceptance criteria:
- Saves to configured disk/path.
- Handles overwrite-on/off behavior predictably.
- Returns metadata (path, bytes, mime).

## Milestone M2 - Multi-Driver Support (Weeks 4-5)
### Epic: Driver Abstraction and Engine Integrations (`type:epic`, `priority:p0`, `area:driver`)
Issue 7: Add Chromium driver (`type:feature`, `priority:p0`, `area:driver`)
- Scope: integrate Chromium rendering with sane defaults.
- Acceptance criteria:
- Generates PDF for sample templates.
- Supports key options (format, margins, print background).
- Error output is normalized to package exceptions.

Issue 8: Add wkhtmltopdf driver (`type:feature`, `priority:p1`, `area:driver`)
- Scope: integrate wkhtmltopdf binary driver.
- Acceptance criteria:
- Binary path/config documented.
- Generates PDFs equivalent to baseline fixture set.

Issue 9: Add dompdf driver (`type:feature`, `priority:p1`, `area:driver`)
- Scope: integrate dompdf adapter.
- Acceptance criteria:
- Works in environments without Chromium.
- Known CSS limitations documented.

Issue 10: Driver capability matrix and fallback policy (`type:feature`, `priority:p1`, `area:driver`)
- Scope: capability flags and warnings for unsupported options per engine.
- Acceptance criteria:
- Unsupported options are surfaced before rendering.
- Capability table published in docs.

## Milestone M3 - Tailwind Compilation and CSS Pipeline (Weeks 6-7)
### Epic: Tailwind-first PDF Styling (`type:epic`, `priority:p0`, `area:tailwind`)
Issue 11: Tailwind compilation pipeline (`type:feature`, `priority:p0`, `area:tailwind`)
- Scope: generate CSS from template classes and inject into render HTML.
- Acceptance criteria:
- Templates with Tailwind classes render styled PDFs.
- Build step can run in CI and production.

Issue 12: CSS cache and invalidation strategy (`type:feature`, `priority:p1`, `area:tailwind`)
- Scope: cache compiled CSS by template/content hash and environment config.
- Acceptance criteria:
- Repeated renders use cache hit path.
- Cache invalidates when template or Tailwind config changes.

Issue 13: CLI tooling for warmup and cache clear (`type:feature`, `priority:p2`, `area:tailwind`)
- Scope: artisan commands to prebuild CSS and clear cache.
- Acceptance criteria:
- Commands run successfully on a fresh install.
- Command help text documents expected usage.

## Milestone M4 - Preview and Debug Tooling (Weeks 8-9)
### Epic: Fast Feedback Loop for Template Authors (`type:epic`, `priority:p0`, `area:preview`)
Issue 14: Implement preview route and controller (`type:feature`, `priority:p0`, `area:preview`)
- Scope: route to preview HTML/PDF with sample data payload.
- Acceptance criteria:
- Preview renders from a named template + payload.
- Route can be toggled per environment.

Issue 15: Debug output utilities (`type:feature`, `priority:p1`, `area:preview`)
- Scope: dump compiled HTML/CSS, render timing, engine logs.
- Acceptance criteria:
- Debug mode writes artifacts to deterministic location.
- Logs include per-step timing in the pipeline.

Issue 16: Page-break helper utilities (`type:feature`, `priority:p2`, `area:preview`)
- Scope: helper classes/directives for common pagination cases.
- Acceptance criteria:
- Helpers work across at least two engines.
- Examples included in docs templates.

## Milestone M5 - Template Registry and Reusability (Weeks 10-11)
### Epic: Reusable Template System (`type:epic`, `priority:p1`, `area:core`)
Issue 17: Template registry API (`type:feature`, `priority:p1`, `area:core`)
- Scope: register, discover, and resolve named templates.
- Acceptance criteria:
- Templates can be referenced by stable keys.
- Registry supports package and app-level overrides.

Issue 18: Starter template pack (`type:feature`, `priority:p2`, `area:core`)
- Scope: invoice, report, certificate templates with sample data.
- Acceptance criteria:
- Each starter template renders successfully in CI fixtures.
- README includes screenshots and usage snippet.

Issue 19: Component conventions and docs (`type:docs`, `priority:p2`, `area:core`)
- Scope: naming, folder structure, data contract conventions.
- Acceptance criteria:
- Documented conventions reviewed and example-complete.

## Milestone M6 - OSS v1 Production Readiness (Week 12)
### Epic: Reliability, Security, and Release (`type:epic`, `priority:p0`, `area:security`)
Issue 20: Secure preview routes and payload handling (`type:feature`, `priority:p0`, `area:security`)
- Scope: auth/guard middleware, environment gate, payload validation.
- Acceptance criteria:
- Preview disabled by default in production.
- Input payload is validated and size-limited.

Issue 21: Queue integration for bulk generation (`type:feature`, `priority:p1`, `area:queue`)
- Scope: queueable render jobs with retries and timeout tuning.
- Acceptance criteria:
- Batch rendering works in queue workers.
- Failed jobs include actionable error context.

Issue 22: Observability hooks (`type:feature`, `priority:p1`, `area:core`)
- Scope: events, metrics, and structured logs around render lifecycle.
- Acceptance criteria:
- Emitted events cover start/success/failure.
- Basic integration example with monitoring backend.

Issue 23: v1.0 release prep (`type:chore`, `priority:p0`, `area:core`)
- Scope: changelog, upgrade notes, tagged release checklist.
- Acceptance criteria:
- Tag is cut from green CI commit.
- Release notes include breaking/non-breaking changes.

## Milestone M7 - Pro Foundations (Weeks 13-16)
### Epic: Pro Backend Foundations (`type:epic`, `priority:p1`, `area:pro`)
Issue 24: Template version history model (`type:feature`, `priority:p1`, `area:pro`)
- Scope: persistent revisions with author and timestamp metadata.
- Acceptance criteria:
- Create/list/restore template versions.
- Diff metadata available for UI consumption.

Issue 25: Team and project boundaries (`type:feature`, `priority:p1`, `area:pro`)
- Scope: workspace-level ownership and access model.
- Acceptance criteria:
- Access checks enforced in core endpoints.
- Permission model documented.

Issue 26: Advanced layout primitives (`type:feature`, `priority:p2`, `area:pro`)
- Scope: page numbers, conditional sections, smart page breaks.
- Acceptance criteria:
- Feature examples pass snapshot tests on target engines.

## Milestone M8 - Visual Builder Prototype (Weeks 17-20)
### Epic: Pro Visual Builder MVP (`type:epic`, `priority:p1`, `area:pro`)
Issue 27: Block-based editor schema (`type:feature`, `priority:p1`, `area:pro`)
- Scope: JSON schema for blocks, style tokens, bindings.
- Acceptance criteria:
- Schema supports invoice/report MVP layouts.
- Schema versioning strategy documented.

Issue 28: Live preview bridge (`type:feature`, `priority:p1`, `area:pro`)
- Scope: UI edits to render-preview loop with low-latency feedback.
- Acceptance criteria:
- Edit-to-preview latency meets target budget.
- Failure states surfaced with clear diagnostics.

Issue 29: Export to Blade templates (`type:feature`, `priority:p1`, `area:pro`)
- Scope: transform builder schema to maintainable Blade + Tailwind output.
- Acceptance criteria:
- Exported templates pass lint and render without manual edits.
- Round-trip constraints documented.

## Milestone M9 - SaaS MVP (Weeks 21-28)
### Epic: Hosted Rendering Platform (`type:epic`, `priority:p1`, `area:saas`)
Issue 30: Auth and tenant model (`type:feature`, `priority:p1`, `area:saas`)
- Scope: account/workspace model, API keys, tenant isolation.
- Acceptance criteria:
- Requests are scoped and isolated per tenant.
- Key rotation and revocation supported.

Issue 31: Hosted rendering API (`type:feature`, `priority:p1`, `area:saas`)
- Scope: create render jobs, poll status, download artifacts.
- Acceptance criteria:
- API supports sync and async workflows.
- OpenAPI spec published and tested.

Issue 32: Usage metering and billing hooks (`type:feature`, `priority:p2`, `area:saas`)
- Scope: count renders/pages/storage and emit billable events.
- Acceptance criteria:
- Metering data is auditable and exportable.
- Billing events are idempotent.

Issue 33: Operations dashboard MVP (`type:feature`, `priority:p2`, `area:saas`)
- Scope: basic analytics for volume, failures, latency.
- Acceptance criteria:
- Dashboard updates from real event stream.
- Filters by date, template, and tenant.

## Suggested Project Setup in GitHub
Issue 34: Create GitHub milestones M0-M9 (`type:chore`, `priority:p0`)
- Acceptance criteria:
- Every issue above mapped to a milestone.

Issue 35: Create project board columns (`type:chore`, `priority:p1`)
- Scope: `Backlog`, `Ready`, `In Progress`, `In Review`, `Done`, `Blocked`.
- Acceptance criteria:
- All open issues are triaged into one column.

Issue 36: Define Definition of Done checklist (`type:chore`, `priority:p1`)
- Scope: tests, docs, changelog, security review, perf check.
- Acceptance criteria:
- DoD checklist template attached to feature issues.

## Dependency Order (Critical Path)
1. M0 -> M1 -> M2 -> M3 -> M4 -> M6 (required for OSS v1 launch).
2. M5 can run in parallel with late M4.
3. Pro track starts after M6 baseline stability.
4. SaaS track starts after M7 data models and M8 export flow are stable.
