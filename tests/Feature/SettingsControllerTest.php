<?php

namespace Tests\Feature;

use App\Models\Settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->employee = User::factory()->create();
        $this->employee->assignRole('employee');
    }

    // ── Access control ────────────────────────────────────────────────────────

    public function test_guest_cannot_access_settings(): void
    {
        $this->get(route('settings.edit'))->assertRedirect(route('login'));
    }

    public function test_employee_cannot_access_settings(): void
    {
        $this->actingAs($this->employee)
            ->get(route('settings.edit'))
            ->assertForbidden();
    }

    public function test_admin_can_access_settings(): void
    {
        $this->actingAs($this->admin)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Settings/Index')->has('settings'));
    }

    public function test_employee_cannot_update_settings(): void
    {
        $this->actingAs($this->employee)
            ->post(route('settings.update'), ['tva_rate' => 20])
            ->assertForbidden();
    }

    // ── Single-row enforcement ────────────────────────────────────────────────

    public function test_settings_row_is_created_on_first_access(): void
    {
        $this->assertDatabaseCount('settings', 0);

        $this->actingAs($this->admin)->get(route('settings.edit'));

        $this->assertDatabaseCount('settings', 1);
    }

    public function test_repeated_access_does_not_create_duplicate_rows(): void
    {
        $this->actingAs($this->admin)->get(route('settings.edit'));
        $this->actingAs($this->admin)->get(route('settings.edit'));

        $this->assertDatabaseCount('settings', 1);
    }

    // ── Update — company info ─────────────────────────────────────────────────

    public function test_admin_can_update_company_info(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.update'), [
                'company_name'    => 'Direct Auto Care SARL',
                'company_address' => '12 Rue Hassan II, Casablanca',
                'company_ice'     => '002345678000056',
                'company_if'      => '12345678',
                'company_rc'      => 'RC-2024-001',
                'tva_rate'        => 20,
            ])
            ->assertRedirect(route('settings.edit'));

        $this->assertDatabaseHas('settings', [
            'company_name' => 'Direct Auto Care SARL',
            'company_ice'  => '002345678000056',
            'tva_rate'     => 20,
        ]);
    }

    public function test_tva_rate_is_required(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.update'), ['tva_rate' => ''])
            ->assertSessionHasErrors('tva_rate');
    }

    public function test_tva_rate_must_be_between_0_and_100(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.update'), ['tva_rate' => 150])
            ->assertSessionHasErrors('tva_rate');
    }

    // ── Update — colors ───────────────────────────────────────────────────────

    public function test_admin_can_update_brand_colors(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.update'), [
                'tva_rate'           => 20,
                'color_primary'      => '#123456',
                'color_accent'       => '#ABCDEF',
                'color_bg'           => '#FFFFFF',
                'color_text_primary' => '#000000',
                'color_text_muted'   => '#888888',
                'color_success'      => '#00FF00',
                'color_warning'      => '#FFAA00',
                'color_error'        => '#FF0000',
            ])
            ->assertRedirect(route('settings.edit'));

        $this->assertDatabaseHas('settings', ['color_primary' => '#123456']);
    }

    public function test_invalid_hex_color_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('settings.update'), [
                'tva_rate'      => 20,
                'color_primary' => 'not-a-color',
            ])
            ->assertSessionHasErrors('color_primary');
    }

    // ── Update — logo ─────────────────────────────────────────────────────────

    public function test_admin_can_upload_logo(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post(route('settings.update'), [
                'tva_rate' => 20,
                'logo'     => UploadedFile::fake()->image('logo.png', 100, 100),
            ])
            ->assertRedirect(route('settings.edit'));

        $settings = Settings::first();
        $this->assertNotNull($settings->logo_path);
        Storage::disk('public')->assertExists($settings->logo_path);
    }

    public function test_logo_url_uses_public_disk(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)->post(route('settings.update'), [
            'tva_rate' => 20,
            'logo'     => UploadedFile::fake()->image('logo.png', 100, 100),
        ]);

        $url = Settings::first()->logoUrl();
        $this->assertStringStartsWith('/storage/', $url);
    }

    public function test_old_logo_is_deleted_on_new_upload(): void
    {
        Storage::fake('public');

        // Upload initial logo
        $this->actingAs($this->admin)->post(route('settings.update'), [
            'tva_rate' => 20,
            'logo'     => UploadedFile::fake()->image('logo1.png'),
        ]);
        $firstPath = Settings::first()->logo_path;

        // Upload replacement logo
        $this->actingAs($this->admin)->post(route('settings.update'), [
            'tva_rate' => 20,
            'logo'     => UploadedFile::fake()->image('logo2.png'),
        ]);

        Storage::disk('public')->assertMissing($firstPath);
    }

    public function test_invalid_logo_mime_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post(route('settings.update'), [
                'tva_rate' => 20,
                'logo'     => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('logo');
    }

    public function test_oversized_logo_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post(route('settings.update'), [
                'tva_rate' => 20,
                'logo'     => UploadedFile::fake()->create('big.png', 3000, 'image/png'),
            ])
            ->assertSessionHasErrors('logo');
    }

    // ── Branding CSS endpoint ─────────────────────────────────────────────────

    public function test_branding_css_returns_css_content_type(): void
    {
        $this->get(route('settings.branding-css'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=utf-8')
            ->assertHeader('Cache-Control', 'max-age=86400, public');
    }

    public function test_branding_css_contains_default_colors(): void
    {
        $response = $this->get(route('settings.branding-css'));

        $response->assertOk();
        $this->assertStringContainsString('#F4A81C', $response->getContent());
        $this->assertStringContainsString('#2FA84F', $response->getContent());
    }

    public function test_branding_css_reflects_updated_colors(): void
    {
        $this->actingAs($this->admin)->post(route('settings.update'), [
            'tva_rate'      => 20,
            'color_primary' => '#AABBCC',
        ]);

        $response = $this->get(route('settings.branding-css'));
        $this->assertStringContainsString('#AABBCC', $response->getContent());
    }
}
