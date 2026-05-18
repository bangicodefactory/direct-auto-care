<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Settings;
use App\Services\SettingsService;
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
            'settings' => array_merge($settings->toArray(), [
                'logo_url' => $settings->logoUrl(),
            ]),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('logo');

        $this->settingsService->update($data, $request->file('logo'));

        return redirect()->route('settings.edit')->with('success', 'Paramètres enregistrés.');
    }

    public function brandingCss(): HttpResponse
    {
        $settings = $this->settingsService->get();

        $css = <<<CSS
        :root {
            --brand-primary: {$settings->color_primary};
            --brand-accent: {$settings->color_accent};
            --brand-bg: {$settings->color_bg};
            --brand-text-primary: {$settings->color_text_primary};
            --brand-text-muted: {$settings->color_text_muted};
            --brand-success: {$settings->color_success};
            --brand-warning: {$settings->color_warning};
            --brand-error: {$settings->color_error};
        }
        CSS;

        return response($css, 200, ['Content-Type' => 'text/css; charset=utf-8']);
    }
}
