<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Customer::insert([
            [
                'first_name' => 'Youssef',
                'last_name' => 'El Amrani',
                'phone' => '0612345678',
                'email' => 'youssef@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'Sara',
                'last_name' => 'Bennani',
                'phone' => '0623456789',
                'email' => 'sara@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'Omar',
                'last_name' => 'Haddad',
                'phone' => '0634567890',
                'email' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
