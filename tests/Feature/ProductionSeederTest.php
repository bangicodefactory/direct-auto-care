<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_roles_service_catalog_and_admin(): void
    {
        $this->seed(ProductionSeeder::class);

        $this->assertTrue(Role::where('name', 'admin')->exists());
        $this->assertTrue(Role::where('name', 'employee')->exists());
        $this->assertDatabaseHas('service_types', []);
        $this->assertDatabaseHas('services', ['name' => 'Vidange']);

        $admin = User::where('email', env('ADMIN_EMAIL', 'admin@directautocare.ma'))->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_does_not_seed_demo_transactional_data(): void
    {
        $this->seed(ProductionSeeder::class);

        // The production baseline must never load fake client data.
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('vehicles', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_is_idempotent(): void
    {
        $this->seed(ProductionSeeder::class);
        $this->seed(ProductionSeeder::class);

        $this->assertSame(1, User::where('email', env('ADMIN_EMAIL', 'admin@directautocare.ma'))->count());
        $this->assertSame(2, Role::count());
        $this->assertSame(7, Service::count());
    }
}
