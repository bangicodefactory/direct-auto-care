<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Per-deployment flags exposed to every Inertia page via HandleInertiaRequests.
 */
class SharedPropsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_show_product_units_is_shared_and_defaults_to_true(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('appSettings.show_product_units', true));
    }

    public function test_show_product_units_follows_config(): void
    {
        config(['company.show_product_units' => false]);

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('appSettings.show_product_units', false));
    }

    public function test_unit_labels_are_shared_in_the_users_locale(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('translations.ui.units.paire.one', 'paire')
                ->where('translations.ui.units.paire.other', 'paires'));

        $this->admin->update(['locale' => 'ar']);

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('translations.ui.units.paire.other', 'أزواج'));
    }
}
