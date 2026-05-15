<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class ServiceTypeSeeder extends Seeder
{
    private const TYPES = [
        'Vidange',
        'Filtres',
        'Freins',
        'Batterie',
        'Pneus',
        'Climatisation',
        'Réparations',
    ];

    public function run(): void
    {
        foreach (self::TYPES as $name) {
            ServiceType::firstOrCreate(['name' => $name]);
        }
    }
}
