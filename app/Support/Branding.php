<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Per-client white-label branding, resolved from `config('company')` (which reads
 * the deployment's .env). Turns the provisioning-time hex theme into the runtime
 * CSS custom properties consumed by the shared front-end bundle, and resolves the
 * logo to a browser URL. See GET /branding.css and HandleInertiaRequests.
 */
class Branding
{
    /**
     * Map of shadcn/ui CSS custom properties → HSL triple ("H S% L%"), built from
     * the configured theme. Only the tokens that visibly define a brand are
     * overridden; the warm-neutral surface tokens keep their app.css defaults.
     *
     * @return array<string, string>
     */
    public static function cssTokens(): array
    {
        /** @var array<string, string> $theme */
        $theme = config('company.theme');

        $primary = self::hexToHsl($theme['primary']);
        $success = self::hexToHsl($theme['accent']);
        $bg      = self::hexToHsl($theme['bg']);
        $text    = self::hexToHsl($theme['text']);

        return [
            '--primary'         => $primary,
            '--ring'            => $primary,
            '--sidebar-primary' => $primary,
            '--sidebar-ring'    => $primary,
            '--warning'         => self::hexToHsl($theme['warning']),
            '--success'         => $success,
            '--tertiary'        => $success,
            '--destructive'     => self::hexToHsl($theme['error']),
            '--background'      => $bg,
            '--surface'         => $bg,
            '--foreground'      => $text,
            '--card-foreground' => $text,
            '--muted-foreground' => self::hexToHsl($theme['text_muted']),
        ];
    }

    /**
     * Browser URL for the configured logo, or null when unset. Absolute URLs are
     * used as-is; anything else is treated as a public-relative asset path.
     */
    public static function logoUrl(): ?string
    {
        $logo = config('company.logo');

        if (! is_string($logo) || $logo === '') {
            return null;
        }

        if (Str::startsWith($logo, ['http://', 'https://'])) {
            return $logo;
        }

        return asset(ltrim($logo, '/'));
    }

    /**
     * Convert a hex colour (#RGB or #RRGGBB) to a space-separated HSL triple
     * ("H S% L%") as used by the shadcn CSS variables, e.g. "#F4A81C" → "39 91% 53%".
     */
    public static function hexToHsl(string $hex): string
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;

        $l = ($max + $min) / 2;

        if ($delta == 0.0) {
            $h = 0.0;
            $s = 0.0;
        } else {
            $s = $delta / (1 - abs(2 * $l - 1));

            $h = match ($max) {
                $r => fmod((($g - $b) / $delta), 6),
                $g => (($b - $r) / $delta) + 2,
                default => (($r - $g) / $delta) + 4,
            } * 60;

            if ($h < 0) {
                $h += 360;
            }
        }

        return sprintf('%d %d%% %d%%', round($h), round($s * 100), round($l * 100));
    }
}
