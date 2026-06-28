# Bangicode Design System

Source of truth for all UI decisions in Direct Auto Care. When in doubt, defer to this document.

---

## Brand identity

| Token | Value | Hex |
|---|---|---|
| Primary (Navy) | `--primary` | `#002058` |
| Secondary (Sky Blue) | `--secondary` | `#006397` |
| Accent / Tertiary (Tech Red) | `--accent` / `--tertiary` | `#500000` |
| Error | `--destructive` | `#ba1a1a` |
| Surface | `--background` | `#f7f9ff` |
| On-surface | `--foreground` | `#091d2e` |

---

## Color system

Built on Material Design 3 (M3) naming. shadcn standard tokens are bridged to the closest M3 equivalent. Custom `--surface-*` and `--primary-container` variables cover the remaining M3 roles.

### Light mode tokens (`:root`)

```css
/* shadcn-standard tokens — HSL space-separated */
--background: 218 100% 98%;          /* #f7f9ff surface */
--foreground: 207 71% 11%;           /* #091d2e on-surface */

--card: 0 0% 100%;                   /* #ffffff surface-container-lowest */
--card-foreground: 207 71% 11%;

--popover: 0 0% 100%;
--popover-foreground: 207 71% 11%;

--primary: 219 100% 17%;             /* #002058 Navy */
--primary-foreground: 0 0% 100%;

--secondary: 202 100% 30%;           /* #006397 Sky Blue */
--secondary-foreground: 0 0% 100%;

--muted: 215 67% 87%;                /* #c9dcf3 surface-dim */
--muted-foreground: 230 8% 30%;      /* #444650 on-surface-variant */

--accent: 6 100% 16%;                /* #500000 Tech Red */
--accent-foreground: 0 0% 100%;

--destructive: 0 75% 41%;            /* #ba1a1a error */
--destructive-foreground: 0 0% 100%;

--border: 232 22% 79%;               /* #c4c6d1 outline-variant */
--input: 232 22% 79%;
--ring: 202 100% 30%;                /* #006397 focus ring */

--radius: 0.25rem;                   /* 4px global corner radius */

/* M3 extensions */
--surface: 218 100% 98%;
--surface-container-low: 213 100% 95%;
--surface-container: 213 100% 92%;
--surface-container-high: 211 100% 90%;
--surface-container-highest: 209 92% 87%;
--primary-container: 222 64% 28%;
--on-primary-container: 222 64% 72%;
--secondary-container: 204 96% 67%;
--tertiary: 6 100% 16%;
--tertiary-container: 0 100% 24%;
```

### Dark mode

**Decision**: light-only for v1. Garage staff use mobile/desktop in daylight; dark mode would add 30-40% scope with no client requirement. Revisit in v1.1 if client requests it.

---

## Typography

All fonts served via Google Fonts (free tier, no licensing cost).

| Role | Font | Weights | Tailwind class |
|---|---|---|---|
| Headings / hero | Oswald | 400, 500, 600, 700 | `font-heading` |
| Body / UI labels | IBM Plex Sans | 400, 500, 600, 700 | `font-sans` (default) |
| Code / mono labels | IBM Plex Mono | 400, 500, 600 | `font-mono` |
| Arabic (bilingual) | Cairo | 400, 600, 700, 900 | `font-arabic` |

### Type scale

| Token | Size | Line height | Weight | Use |
|---|---|---|---|---|
| `text-display-lg` | 48px | 56px | 700 | Hero / page title (landing) |
| `text-headline-lg` | 32px | 40px | 600 | Section headings |
| `text-headline-md` | 24px | 32px | 600 | Card titles, modal headings |
| `text-body-lg` | 18px | 28px | 400 | Lead paragraphs |
| `text-body-md` | 16px | 24px | 400 | Default body text |
| `text-label-mono` | 14px | 20px | 500 | Status chips, SKU labels (mono) |
| `text-label-sm` | 12px | 16px | 600 | Helper text, badges |

---

## Spacing

4px base unit. Standard Tailwind scale (1 unit = 4px).

---

## Corner radius

| Token | Size | Use |
|---|---|---|
| `rounded-sm` | 2px | Very tight (chips, small tags) |
| `rounded` (default) | 4px | Inputs, buttons, cards |
| `rounded-md` | 6px | Modals, larger cards |
| `rounded-lg` | 8px | Dialogs, sheets |
| `rounded-xl` | 12px | Bottom sheets on mobile |
| `rounded-full` | 9999px | Avatars, pill badges |

---

## Elevation / shadow

| Token | Value | Use |
|---|---|---|
| `shadow-tech` | `0px 4px 12px rgba(26, 54, 115, 0.08)` | Cards on hover, dropdowns |
| No shadow | — | Default card state |

Cards use a `1px --border` border at rest, border → `--secondary` (Sky Blue) + `shadow-tech` on hover.

---

## Component guidelines

### Button

| Variant | Background | Text | Border | Hover |
|---|---|---|---|---|
| `default` (Primary) | `--primary` Navy | White | — | 90% opacity |
| `secondary` | `--secondary` Sky Blue | White | — | 80% opacity |
| `outline` | Transparent | — | `--input` | `--secondary` bg + white text |
| `ghost` | Transparent | — | — | `--secondary` bg + white text |
| `destructive` | `--destructive` | White | — | 90% opacity |
| `accent` | `--accent` Tech Red | White | — | 90% opacity |
| `link` | Transparent | `--primary` | — | Underline |

Radius: `rounded-md` (6px). Height: 40px default, 36px sm, 44px lg.

### Input

White background, 1px `--border`, 4px radius. Focus: border → `--ring` Sky Blue + `focus:ring-2 focus:ring-secondary/20`.

### Card

White bg, 1px `--border`, no default shadow. Hover: border → `--secondary`, add `shadow-tech`.

### Sidebar navigation

Background: `--primary` Navy `#002058`. Active indicator: `--secondary` Sky Blue left border (3px). Text: white at 87% opacity, 100% when active.

### Status chips / tags

Font: `font-mono text-label-mono`. Background: 10% opacity of the brand color. Example: `bg-secondary/10 text-secondary`.

### Focus ring

All interactive elements: `focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2`. Ring color = `--ring` Sky Blue `#006397`.

---

## shadcn primitives in use

Installed in `resources/js/Components/ui/`:

`accordion`, `alert`, `avatar`, `badge`, `breadcrumb`, `button`, `calendar`, `card`, `checkbox`, `command`, `dialog`, `dropdown-menu`, `form`, `input`, `label`, `popover`, `radio-group`, `select`, `separator`, `sheet`, `skeleton`, `sonner`, `switch`, `table`, `tabs`, `textarea`, `tooltip`

---

## Tailwind custom utilities

```js
// tailwind.config.js extend
fontFamily: {
  heading: ['Oswald', 'sans-serif'],
  sans: ['IBM Plex Sans', ...defaultTheme.fontFamily.sans],
  mono: ['IBM Plex Mono', ...defaultTheme.fontFamily.mono],
  arabic: ['Cairo', ...defaultTheme.fontFamily.sans],
}
boxShadow: {
  tech: '0px 4px 12px rgba(26, 54, 115, 0.08)',
}
```

---

## Notification system

Toasts via **sonner** (`<Toaster />` mounted in root layout). Never use `alert()`. Import from `@/Components/ui/sonner`.

```jsx
import { toast } from 'sonner';
toast.success('Client enregistré.');
toast.error('Erreur de validation.');
```

---

## Localization

- French is the default and only UI language for v1.
- Date format: `dd/mm/yyyy`. Currency: MAD (Dh).
- All user-facing strings via `trans()` (PHP) or i18n (JS).

---

## Out of scope for v1

- Dark mode (defer to v1.1)
- Runtime color customization via Settings — tokens are build-time, locked to Bangicode brand
