<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(ServiceTypeSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@directautocare.ma')],
            [
                'name' => env('ADMIN_NAME', 'Administrateur'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('admin');
        $this->call(CustomerSeeder::class);
        $this->call(VehicleSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(InvoiceSeeder::class);
    }
}
