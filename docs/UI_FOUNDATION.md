# Aurevia Health UI Foundation

Aurevia Health uses a compact, safety-oriented application shell designed for dense clinical and operational workflows.

## Frontend baseline

- Angular 22
- Server-side rendering enabled
- Tailwind CSS 4.3
- Tailwind PostCSS integration
- CSS-first theme tokens
- Compact vertical spacing
- Standalone Angular architecture

## Density rules

The interface deliberately avoids oversized SaaS spacing. The initial targets are:

| Primitive | Baseline |
|---|---:|
| Top bar | 52px |
| Sidebar | 244px |
| Page gutter | 16–18px |
| Section gap | 14px |
| Card padding | 14px |
| Standard control | 36px |
| Compact control | 32px |
| Tab | 36px |
| Table row | 40px |

Density must not reduce hit targets for high-risk actions or make clinical status ambiguous.

## Global classes

### Layout

- `ah-shell`
- `ah-sidebar`
- `ah-sidebar-brand`
- `ah-sidebar-nav`
- `ah-main`
- `ah-topbar`
- `ah-content`
- `ah-page-header`
- `ah-section`
- `ah-grid`

### Buttons

Combine `ah-btn` with one variant:

- `ah-btn-primary`
- `ah-btn-secondary`
- `ah-btn-ghost`
- `ah-btn-danger`
- `ah-btn-sm`
- `ah-icon-btn`

Buttons include hover, active, disabled and focus-visible states.

### Links and navigation

- `ah-link`
- `ah-link-subtle`
- `ah-nav-link`
- `ah-nav-icon`

### Tabs

- `ah-tabs`
- `ah-tab`
- `ah-tabs-pills`

Use `aria-selected="true"` for active tabs. `is-active` is supported for static/demo markup.

### Forms

- `ah-label`
- `ah-input`
- `ah-select`
- `ah-textarea`

### Cards and data display

- `ah-card`
- `ah-card-hover`
- `ah-card-header`
- `ah-card-title`
- `ah-card-body`
- `ah-table-wrap`
- `ah-table`
- `ah-badge`

Status badge variants:

- `ah-badge-info`
- `ah-badge-success`
- `ah-badge-warning`
- `ah-badge-danger`

## Clinical UX rules

1. Never encode critical state by color alone.
2. Preserve visible focus states for keyboard users.
3. Do not use decorative animation on high-risk clinical actions.
4. Keep hover motion subtle and short.
5. Use destructive styling only for genuinely destructive actions.
6. Patient identity, allergies, critical alerts and signed-record state will receive dedicated components rather than generic badges once those workflows are implemented.
7. Dense layouts must remain readable at browser zoom and common laptop resolutions.

## Setup

With the Docker development stack running:

```powershell
scripts\frontend-theme.cmd
```

The helper installs Tailwind dependencies in `apps/web`, switches Angular's global stylesheet from `src/styles.scss` to `src/styles.css`, and installs the Aurevia starter shell.

Verify dependencies:

```powershell
docker exec aurevia-health-web npm list tailwindcss @tailwindcss/postcss postcss
```

Then open:

```text
http://localhost:4200
```
