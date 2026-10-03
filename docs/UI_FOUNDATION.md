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

## Aurevia visual identity

Aurevia Health should not read as a generic all-white admin dashboard. The product identity uses a light blue-teal navigation rail with strong ink text, a cool tinted workspace, cobalt and teal brand accents, and restrained supporting surface tints. White is reserved primarily for elevated content such as forms, tables, dialogs and other areas where contrast is useful.

The navigation rail must remain clearly distinct from the workspace without relying on a dark background. Brand, navigation and account text use high-contrast ink/slate tones; the selected item uses a teal indicator with a blue-teal tint rather than reversed light text on a dark surface.

| Role | Token | Baseline |
|---|---|---|
| Brand ink | `--ah-color-brand-ink` | `#172033` |
| Brand cobalt | `--ah-color-brand-cobalt` | `#3867e8` |
| Brand teal | `--ah-color-brand-teal` | `#0f8f87` |
| Workspace canvas | `--ah-color-canvas` | `#eef3f7` |
| Default surface | `--ah-color-surface` | `#f8fafc` |
| Elevated surface | `--ah-color-surface-raised` | `#ffffff` |
| Aqua tint | `--ah-color-tint-aqua` | `#e4f6f3` |
| Blue tint | `--ah-color-tint-blue` | `#e9eeff` |
| Violet tint | `--ah-color-tint-violet` | `#f0ebff` |
| Warm tint | `--ah-color-tint-warm` | `#fff3df` |

Brand color and clinical status color have different jobs. Cobalt, teal and the supporting tints provide product identity. Green, amber and red remain reserved for success/safe state, attention and danger/critical meaning; they must not be used as decorative brand colors.

The design system provides optional card surface classes for intentional section differentiation:

- `ah-card-tone-aqua`
- `ah-card-tone-blue`
- `ah-card-tone-violet`
- `ah-card-tone-warm`

The overview summary uses the same surface language so the mock demonstrates the palette without requiring clinical status to be encoded by card color.

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
