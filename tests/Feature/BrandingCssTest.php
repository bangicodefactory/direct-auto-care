<?php

namespace Tests\Feature;

use App\Support\Branding;
use Tests\TestCase;

class BrandingCssTest extends TestCase
{
    public function test_branding_css_returns_css_content_type(): void
    {
        $this->get(route('branding-css'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=utf-8')
            ->assertHeader('Cache-Control', 'max-age=86400, public');
    }

    public function test_branding_css_contains_default_theme_tokens(): void
    {
        $response = $this->get(route('branding-css'));

        $response->assertOk();
        // Emitted as HSL shadcn tokens from config('company.theme'), not hex.
        $this->assertStringContainsString('--primary: 39 91% 53%;', $response->getContent());
        $this->assertStringContainsString('--success: 136 56% 42%;', $response->getContent());
    }

    public function test_branding_css_reflects_configured_theme(): void
    {
        config(['company.theme.primary' => '#AABBCC']);

        $response = $this->get(route('branding-css'));

        $this->assertStringContainsString(
            '--primary: '.Branding::hexToHsl('#AABBCC').';',
            $response->getContent()
        );
    }
}
