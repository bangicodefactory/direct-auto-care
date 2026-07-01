// Brand colours for Recharts. Recharts writes `stroke`/`fill` as SVG attributes,
// which do NOT resolve CSS variables, so we read the computed theme tokens at
// runtime (set per-client by /branding.css) and build concrete hsl() strings.
// Falls back to the Direct Auto Care palette during SSR / before styles resolve.
//
// These are functions, not constants, so they must be called inside a component
// render — after the theme stylesheet has been applied to <html>.

function cssColor(varName, fallback) {
    if (typeof window === 'undefined') {
        return fallback;
    }
    const value = window
        .getComputedStyle(document.documentElement)
        .getPropertyValue(varName)
        .trim();
    return value ? `hsl(${value})` : fallback;
}

/** Primary action colour (--primary). */
export function brandPrimary() {
    return cssColor('--primary', '#F4A81C');
}

/** Validation / accent colour (--success). */
export function brandAccent() {
    return cssColor('--success', '#2FA84F');
}
