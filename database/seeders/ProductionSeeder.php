<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * First-deploy seed for a real client: roles, the reference service catalog, and
 * the admin from ADMIN_* — and NONE of the demo transactional data (customers,
 * vehicles, invoices, …). Idempotent (firstOrCreate), so it is safe to re-run.
 * DatabaseSeeder calls this first, then layers the demo data on top for local/demo.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(ServiceTypeSeeder::class);
        $this->call(ServiceSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@directautocare.ma')],
            [
                'name'              => env('ADMIN_NAME', 'Administrateur'),
                'password'          => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('admin');
    }
}
