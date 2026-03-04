#!/usr/bin/env bash
set -euo pipefail

# Bootstrap GitHub milestones/issues for PDF Studio backlog.
# Idempotent behavior:
# - labels are created/updated with --force
# - milestones are created only if missing
# - issues are skipped if an issue with the same exact title already exists

if ! command -v gh >/dev/null 2>&1; then
  echo "Error: GitHub CLI 'gh' is required." >&2
  exit 1
fi

if ! command -v jq >/dev/null 2>&1; then
  echo "Error: 'jq' is required." >&2
  exit 1
fi

if ! gh auth status >/dev/null 2>&1; then
  echo "Error: gh is not authenticated. Run: gh auth login" >&2
  exit 1
fi

REPO="${1:-}"
if [[ -z "$REPO" ]]; then
  REPO="$(gh repo view --json nameWithOwner -q .nameWithOwner)"
fi

if [[ -z "$REPO" ]]; then
  echo "Error: could not determine repo. Pass it explicitly: owner/repo" >&2
  exit 1
fi

echo "Using repo: $REPO"

ensure_label() {
  local name="$1"
  local color="$2"
  local desc="$3"
  gh label create "$name" --repo "$REPO" --color "$color" --description "$desc" --force >/dev/null
}

ensure_milestone() {
  local title="$1"
  local desc="$2"
  local number
  number="$(gh api "repos/$REPO/milestones?state=all&per_page=100" | jq -r --arg t "$title" '.[] | select(.title == $t) | .number' | head -n1)"

  if [[ -z "$number" ]]; then
    gh api -X POST "repos/$REPO/milestones" -f title="$title" -f description="$desc" >/dev/null
    echo "Created milestone: $title"
  else
    echo "Milestone exists: $title"
  fi
}

milestone_number() {
  local title="$1"
  gh api "repos/$REPO/milestones?state=all&per_page=100" | jq -r --arg t "$title" '.[] | select(.title == $t) | .number' | head -n1
}

create_issue_if_missing() {
  local title="$1"
  local milestone="$2"
  local labels_csv="$3"
  local body="$4"

  if grep -Fxq "$title" <<<"$EXISTING_TITLES"; then
    echo "Issue exists, skipping: $title"
    return
  fi

  local args=(--repo "$REPO" --title "$title" --body "$body")

  if [[ -n "$milestone" ]]; then
    local mnum
    mnum="${MILESTONE_NUMBERS[$milestone]:-}"
    if [[ -n "$mnum" ]]; then
      args+=(--milestone "$mnum")
    fi
  fi

  IFS=',' read -r -a label_arr <<<"$labels_csv"
  local lbl
  for lbl in "${label_arr[@]}"; do
    lbl="${lbl#${lbl%%[![:space:]]*}}"
    lbl="${lbl%${lbl##*[![:space:]]}}"
    [[ -n "$lbl" ]] && args+=(--label "$lbl")
  done

  gh issue create "${args[@]}" >/dev/null
  echo "Created issue: $title"

  EXISTING_TITLES+=$'\n'"$title"
}

echo "Ensuring labels..."
ensure_label "type:epic" "5319e7" "Epic-level tracking item"
ensure_label "type:feature" "1d76db" "Feature implementation"
ensure_label "type:bug" "d73a4a" "Bug fix"
ensure_label "type:chore" "6e7781" "Maintenance and project setup"
ensure_label "type:docs" "0e8a16" "Documentation work"
ensure_label "area:core" "0052cc" "Core rendering and package internals"
ensure_label "area:driver" "0b7fbd" "Renderer drivers"
ensure_label "area:tailwind" "1f883d" "Tailwind compilation and CSS"
ensure_label "area:preview" "c2e0c6" "Preview and debug workflow"
ensure_label "area:security" "b60205" "Security and hardening"
ensure_label "area:queue" "fbca04" "Queue and async processing"
ensure_label "area:pro" "a2eeef" "Pro feature track"
ensure_label "area:saas" "bfdadc" "SaaS platform track"
ensure_label "priority:p0" "b60205" "Highest priority"
ensure_label "priority:p1" "fbca04" "Medium priority"
ensure_label "priority:p2" "0e8a16" "Lower priority"

echo "Ensuring milestones..."
ensure_milestone "M0" "Foundation Setup (Week 1)"
ensure_milestone "M1" "Core Rendering Engine (Weeks 2-3)"
ensure_milestone "M2" "Multi-Driver Support (Weeks 4-5)"
ensure_milestone "M3" "Tailwind Compilation and CSS Pipeline (Weeks 6-7)"
ensure_milestone "M4" "Preview and Debug Tooling (Weeks 8-9)"
ensure_milestone "M5" "Template Registry and Reusability (Weeks 10-11)"
ensure_milestone "M6" "OSS v1 Production Readiness (Week 12)"
ensure_milestone "M7" "Pro Foundations (Weeks 13-16)"
ensure_milestone "M8" "Visual Builder Prototype (Weeks 17-20)"
ensure_milestone "M9" "SaaS MVP (Weeks 21-28)"

declare -A MILESTONE_NUMBERS
for m in M0 M1 M2 M3 M4 M5 M6 M7 M8 M9; do
  MILESTONE_NUMBERS["$m"]="$(milestone_number "$m")"
done

EXISTING_TITLES="$(gh issue list --repo "$REPO" --state all --limit 1000 --json title | jq -r '.[].title')"

echo "Creating issues..."

create_issue_if_missing "Package Foundation and CI" "M0" "type:epic,priority:p0,area:core" $'Imported from backlog file.\n\n- Establish package foundation and CI quality gates.'
create_issue_if_missing "Bootstrap Laravel package skeleton" "M0" "type:feature,priority:p0,area:core" $'Scope: service provider, config publish, facade/helper entrypoint, docs scaffold.\n\nAcceptance criteria:\n- Package installs in a fresh Laravel app.\n- Config publish works.\n- Basic smoke example runs.'
create_issue_if_missing "Configure CI and quality gates" "M0" "type:chore,priority:p0,area:core" $'Scope: GitHub Actions for lint, static analysis, and tests matrix.\n\nAcceptance criteria:\n- CI runs on push/PR.\n- Required checks block failing merges.\n- Supported version matrix is covered.'
create_issue_if_missing "Establish coding conventions and contribution docs" "M0" "type:docs,priority:p1,area:core" $'Scope: CONTRIBUTING, coding style, release process notes.\n\nAcceptance criteria:\n- Contribution flow documented.\n- Setup steps verified on clean environment.'

create_issue_if_missing "Unified PDF API" "M1" "type:epic,priority:p0,area:core" $'Imported from backlog file.\n\n- Deliver stable core API for rendering output workflows.'
create_issue_if_missing "Define renderer contract and request DTOs" "M1" "type:feature,priority:p0,area:core" $'Scope: common driver interface and render option DTOs.\n\nAcceptance criteria:\n- Supports view/data/output/options.\n- Contract documented and tested with fake driver.'
create_issue_if_missing "Implement fluent API" "M1" "type:feature,priority:p0,area:core" $'Scope: make/view/data/download/save/stream/render fluent chain.\n\nAcceptance criteria:\n- Chaining works end-to-end.\n- Invalid options raise clear exceptions.\n- Docs example matches runtime behavior.'
create_issue_if_missing "File output and storage support" "M1" "type:feature,priority:p1,area:core" $'Scope: local and disk storage support with overwrite behavior.\n\nAcceptance criteria:\n- Save to configured disk/path.\n- Predictable overwrite behavior.\n- Return path/bytes/mime metadata.'

create_issue_if_missing "Driver Abstraction and Engine Integrations" "M2" "type:epic,priority:p0,area:driver" $'Imported from backlog file.\n\n- Implement and stabilize multi-engine support.'
create_issue_if_missing "Add Chromium driver" "M2" "type:feature,priority:p0,area:driver" $'Scope: Chromium integration with sane defaults.\n\nAcceptance criteria:\n- Renders sample templates.\n- Supports format/margins/print background.\n- Normalized exception output.'
create_issue_if_missing "Add wkhtmltopdf driver" "M2" "type:feature,priority:p1,area:driver" $'Scope: wkhtmltopdf adapter integration.\n\nAcceptance criteria:\n- Binary path/config documented.\n- Fixture set renders successfully.'
create_issue_if_missing "Add dompdf driver" "M2" "type:feature,priority:p1,area:driver" $'Scope: dompdf adapter integration.\n\nAcceptance criteria:\n- Works where Chromium is unavailable.\n- CSS limitations documented.'
create_issue_if_missing "Driver capability matrix and fallback policy" "M2" "type:feature,priority:p1,area:driver" $'Scope: engine capability flags and fallback warnings.\n\nAcceptance criteria:\n- Unsupported options surfaced pre-render.\n- Capability table documented.'

create_issue_if_missing "Tailwind-first PDF Styling" "M3" "type:epic,priority:p0,area:tailwind" $'Imported from backlog file.\n\n- Deliver reliable Tailwind compilation and injection pipeline.'
create_issue_if_missing "Tailwind compilation pipeline" "M3" "type:feature,priority:p0,area:tailwind" $'Scope: compile Tailwind and inject compiled CSS into HTML pipeline.\n\nAcceptance criteria:\n- Tailwind classes render in PDF output.\n- Build step is CI/prod compatible.'
create_issue_if_missing "CSS cache and invalidation strategy" "M3" "type:feature,priority:p1,area:tailwind" $'Scope: hash-based CSS cache and invalidation.\n\nAcceptance criteria:\n- Repeat renders hit cache path.\n- Template/config updates invalidate cache.'
create_issue_if_missing "CLI tooling for warmup and cache clear" "M3" "type:feature,priority:p2,area:tailwind" $'Scope: artisan commands for warmup and cache clear.\n\nAcceptance criteria:\n- Commands run on fresh install.\n- Command help documents usage.'

create_issue_if_missing "Fast Feedback Loop for Template Authors" "M4" "type:epic,priority:p0,area:preview" $'Imported from backlog file.\n\n- Implement preview and debugging workflow.'
create_issue_if_missing "Implement preview route and controller" "M4" "type:feature,priority:p0,area:preview" $'Scope: preview endpoint for HTML/PDF with sample payloads.\n\nAcceptance criteria:\n- Renders named template + payload.\n- Environment toggle supported.'
create_issue_if_missing "Debug output utilities" "M4" "type:feature,priority:p1,area:preview" $'Scope: compiled HTML/CSS dumps, timing, engine log capture.\n\nAcceptance criteria:\n- Debug artifacts stored deterministically.\n- Pipeline timing included in logs.'
create_issue_if_missing "Page-break helper utilities" "M4" "type:feature,priority:p2,area:preview" $'Scope: page-break helpers/directives for common cases.\n\nAcceptance criteria:\n- Works across at least two engines.\n- Example usage documented.'

create_issue_if_missing "Reusable Template System" "M5" "type:epic,priority:p1,area:core" $'Imported from backlog file.\n\n- Build reusable, discoverable template workflow.'
create_issue_if_missing "Template registry API" "M5" "type:feature,priority:p1,area:core" $'Scope: register/discover/resolve named templates.\n\nAcceptance criteria:\n- Stable template keys supported.\n- Package/app override behavior supported.'
create_issue_if_missing "Starter template pack" "M5" "type:feature,priority:p2,area:core" $'Scope: invoice/report/certificate starters with sample payloads.\n\nAcceptance criteria:\n- All starters pass CI fixture render.\n- README usage snippet and screenshots included.'
create_issue_if_missing "Component conventions and docs" "M5" "type:docs,priority:p2,area:core" $'Scope: naming, folder structure, and data contract conventions.\n\nAcceptance criteria:\n- Conventions documented with complete examples.'

create_issue_if_missing "Reliability, Security, and Release" "M6" "type:epic,priority:p0,area:security" $'Imported from backlog file.\n\n- Hardening and release-readiness for OSS v1.'
create_issue_if_missing "Secure preview routes and payload handling" "M6" "type:feature,priority:p0,area:security" $'Scope: auth/middleware guards, env gates, payload validation.\n\nAcceptance criteria:\n- Preview disabled by default in production.\n- Inputs validated with size limits.'
create_issue_if_missing "Queue integration for bulk generation" "M6" "type:feature,priority:p1,area:queue" $'Scope: queueable render jobs with retries/timeouts.\n\nAcceptance criteria:\n- Batch rendering works in workers.\n- Failures include actionable context.'
create_issue_if_missing "Observability hooks" "M6" "type:feature,priority:p1,area:core" $'Scope: lifecycle events, metrics, and structured logs.\n\nAcceptance criteria:\n- Start/success/failure events emitted.\n- Monitoring integration example provided.'
create_issue_if_missing "v1.0 release prep" "M6" "type:chore,priority:p0,area:core" $'Scope: changelog, upgrade notes, release checklist.\n\nAcceptance criteria:\n- Tag from green CI commit.\n- Release notes include breaking/non-breaking changes.'

create_issue_if_missing "Pro Backend Foundations" "M7" "type:epic,priority:p1,area:pro" $'Imported from backlog file.\n\n- Build data/permission foundations for Pro.'
create_issue_if_missing "Template version history model" "M7" "type:feature,priority:p1,area:pro" $'Scope: template revisions with authorship/timestamps.\n\nAcceptance criteria:\n- Create/list/restore versions.\n- Diff metadata exposed.'
create_issue_if_missing "Team and project boundaries" "M7" "type:feature,priority:p1,area:pro" $'Scope: workspace ownership and access checks.\n\nAcceptance criteria:\n- Endpoint authorization enforced.\n- Permission model documented.'
create_issue_if_missing "Advanced layout primitives" "M7" "type:feature,priority:p2,area:pro" $'Scope: page numbers, conditional sections, smart breaks.\n\nAcceptance criteria:\n- Snapshot tests pass for target engines.'

create_issue_if_missing "Pro Visual Builder MVP" "M8" "type:epic,priority:p1,area:pro" $'Imported from backlog file.\n\n- Deliver MVP editor + live preview + export flow.'
create_issue_if_missing "Block-based editor schema" "M8" "type:feature,priority:p1,area:pro" $'Scope: block JSON schema with styles and bindings.\n\nAcceptance criteria:\n- Supports invoice/report layouts.\n- Schema versioning strategy documented.'
create_issue_if_missing "Live preview bridge" "M8" "type:feature,priority:p1,area:pro" $'Scope: low-latency edit-to-render feedback loop.\n\nAcceptance criteria:\n- Latency budget met.\n- Clear diagnostic errors exposed.'
create_issue_if_missing "Export to Blade templates" "M8" "type:feature,priority:p1,area:pro" $'Scope: transform schema into maintainable Blade + Tailwind.\n\nAcceptance criteria:\n- Export passes lint and renders without manual edits.\n- Round-trip constraints documented.'

create_issue_if_missing "Hosted Rendering Platform" "M9" "type:epic,priority:p1,area:saas" $'Imported from backlog file.\n\n- Build SaaS rendering platform MVP.'
create_issue_if_missing "Auth and tenant model" "M9" "type:feature,priority:p1,area:saas" $'Scope: account/workspace model, API key management, tenant isolation.\n\nAcceptance criteria:\n- Tenant-scoped isolation enforced.\n- Key rotation/revocation supported.'
create_issue_if_missing "Hosted rendering API" "M9" "type:feature,priority:p1,area:saas" $'Scope: job create/status/download APIs.\n\nAcceptance criteria:\n- Supports sync + async flows.\n- OpenAPI spec published and tested.'
create_issue_if_missing "Usage metering and billing hooks" "M9" "type:feature,priority:p2,area:saas" $'Scope: billable usage event metering for renders/pages/storage.\n\nAcceptance criteria:\n- Metering auditable/exportable.\n- Billing events idempotent.'
create_issue_if_missing "Operations dashboard MVP" "M9" "type:feature,priority:p2,area:saas" $'Scope: dashboard for volume, failures, and latency analytics.\n\nAcceptance criteria:\n- Event-driven updates.\n- Date/template/tenant filters available.'

create_issue_if_missing "Create GitHub milestones M0-M9" "" "type:chore,priority:p0" $'Scope: ensure all milestones exist and are used.\n\nAcceptance criteria:\n- All issues mapped to milestones where applicable.'
create_issue_if_missing "Create project board columns" "" "type:chore,priority:p1" $'Scope: Backlog, Ready, In Progress, In Review, Done, Blocked.\n\nAcceptance criteria:\n- Open issues triaged into one column.'
create_issue_if_missing "Define Definition of Done checklist" "" "type:chore,priority:p1" $'Scope: tests, docs, changelog, security, performance checks.\n\nAcceptance criteria:\n- DoD checklist template attached to feature issues.'

echo
echo "Backlog bootstrap complete for $REPO"
