<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * @var array<int, array{name: string, default_price_ht: float}>
     */
    private const SERVICES = [
        ['name' => 'Vidange', 'default_price_ht' => 150.00],
        ['name' => 'Recharge climatisation', 'default_price_ht' => 350.00],
        ['name' => 'Contrôle général', 'default_price_ht' => 200.00],
        ['name' => 'Changement filtre à air', 'default_price_ht' => 80.00],
        ['name' => 'Remplacement plaquettes de frein', 'default_price_ht' => 180.00],
        ['name' => 'Équilibrage / parallélisme', 'default_price_ht' => 250.00],
        ['name' => 'Diagnostic électronique', 'default_price_ht' => 200.00],
    ];

    public function run(): void
    {
        foreach (self::SERVICES as $service) {
            Service::firstOrCreate(['name' => $service['name']], $service);
        }
    }
}
