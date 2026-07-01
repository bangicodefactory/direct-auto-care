<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Settings;
use App\Services\SettingsService;
use App\Support\Branding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settingsService)
    {
    }

    public function edit(): Response
    {
        $this->authorize('viewAny', Settings::class);

        $settings = $this->settingsService->get();

        return Inertia::render('Settings/Index', [
            'settings' => array_merge(
                $settings->only([
                    'company_name', 'company_address', 'company_ice', 'company_if', 'company_rc',
                    'tva_rate', 'color_primary', 'color_accent', 'color_bg', 'color_text_primary',
                    'color_text_muted', 'color_success', 'color_warning', 'color_error',
                ]),
                ['logo_url' => $settings->logoUrl()]
            ),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('logo');

        $this->settingsService->update($data, $request->file('logo'));

        return redirect()->route('settings.edit')->with('success', 'Paramètres enregistrés.');
    }

    /**
     * Per-client theme, emitted as an unlayered `:root` block that overrides the
     * design-system defaults in app.css (which live in `@layer base`). Colours
     * come from `config('company.theme')` — the deployment's .env — so the shared
     * compiled bundle themes itself per client without a rebuild.
     */
    public function brandingCss(): HttpResponse
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
