<?php

namespace App\Http\Controllers;

use App\Support\Branding;
use Illuminate\Http\Response;

class BrandingController extends Controller
{
    /**
     * Per-client theme, emitted as an unlayered `:root` block that overrides the
     * design-system defaults in app.css (which live in `@layer base`). Colours
     * come from `config('company.theme')` — the deployment's .env — so the shared
     * compiled bundle themes itself per client without a rebuild.
     */
    public function css(): Response
    {
        $declarations = collect(Branding::cssTokens())
            ->map(fn (string $value, string $token) => "    {$token}: {$value};")
            ->implode("\n");

        $css = ":root {\n{$declarations}\n}\n";

        return response($css, 200, [
            'Content-Type'  => 'text/css; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
