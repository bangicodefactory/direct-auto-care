<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            // Huiles moteur
            [
                'category' => 'huile', 'name' => 'Huile moteur 5W-30 1L',
                'sku' => 'HU-5W30-1L', 'unit' => 'litre',
                'price_buy' => 35.00, 'price_sell' => 55.00,
                'quantity_on_hand' => 30, 'low_stock_threshold' => 5,
            ],
            [
                'category' => 'huile', 'name' => 'Huile moteur 5W-30 5L',
                'sku' => 'HU-5W30-5L', 'unit' => 'litre',
                'price_buy' => 155.00, 'price_sell' => 230.00,
                'quantity_on_hand' => 20, 'low_stock_threshold' => 3,
            ],
            [
                'category' => 'huile', 'name' => 'Huile moteur 10W-40 1L',
                'sku' => 'HU-10W40-1L', 'unit' => 'litre',
                'price_buy' => 28.00, 'price_sell' => 45.00,
                'quantity_on_hand' => 25, 'low_stock_threshold' => 5,
            ],
            [
                'category' => 'huile', 'name' => 'Huile moteur 10W-40 5L',
                'sku' => 'HU-10W40-5L', 'unit' => 'litre',
                'price_buy' => 130.00, 'price_sell' => 195.00,
                'quantity_on_hand' => 15, 'low_stock_threshold' => 3,
            ],
            // Filtres à huile
            [
                'category' => 'filtre', 'name' => 'Filtre à huile MANN W712/75',
                'sku' => 'FH-MANN-W71275', 'unit' => 'unite',
                'price_buy' => 22.00, 'price_sell' => 45.00,
                'quantity_on_hand' => 20, 'low_stock_threshold' => 5,
            ],
            [
                'category' => 'filtre', 'name' => 'Filtre à huile Bosch P7051',
                'sku' => 'FH-BOSCH-P7051', 'unit' => 'unite',
                'price_buy' => 25.00, 'price_sell' => 50.00,
                'quantity_on_hand' => 18, 'low_stock_threshold' => 5,
            ],
            [
                'category' => 'filtre', 'name' => 'Filtre à huile Mahle OC90',
                'sku' => 'FH-MAHLE-OC90', 'unit' => 'unite',
                'price_buy' => 20.00, 'price_sell' => 42.00,
                'quantity_on_hand' => 12, 'low_stock_threshold' => 5,
            ],
            // Filtres à air
            [
                'category' => 'filtre', 'name' => 'Filtre à air MANN C24004',
                'sku' => 'FA-MANN-C24004', 'unit' => 'unite',
                'price_buy' => 38.00, 'price_sell' => 75.00,
                'quantity_on_hand' => 10, 'low_stock_threshold' => 3,
            ],
            [
                'category' => 'filtre', 'name' => 'Filtre à air Bosch S0044',
                'sku' => 'FA-BOSCH-S0044', 'unit' => 'unite',
                'price_buy' => 42.00, 'price_sell' => 85.00,
                'quantity_on_hand' => 8, 'low_stock_threshold' => 3,
            ],
            // Filtre d'habitacle
            [
                'category' => 'filtre', 'name' => "Filtre d'habitacle MANN CU2442",
                'sku' => 'FC-MANN-CU2442', 'unit' => 'unite',
                'price_buy' => 30.00, 'price_sell' => 60.00,
                'quantity_on_hand' => 10, 'low_stock_threshold' => 3,
            ],
            // Plaquettes de frein
            [
                'category' => 'piece', 'name' => 'Plaquettes de frein avant Brembo',
                'sku' => 'PF-BREMBO-AV', 'unit' => 'paire',
                'price_buy' => 120.00, 'price_sell' => 220.00,
                'quantity_on_hand' => 8, 'low_stock_threshold' => 2,
            ],
            [
                'category' => 'piece', 'name' => 'Plaquettes de frein arrière Brembo',
                'sku' => 'PF-BREMBO-AR', 'unit' => 'paire',
                'price_buy' => 95.00, 'price_sell' => 175.00,
                'quantity_on_hand' => 6, 'low_stock_threshold' => 2,
            ],
            // Disques de frein
            [
                'category' => 'piece', 'name' => 'Disques de frein avant (paire)',
                'sku' => 'DF-AV-PAIRE', 'unit' => 'paire',
                'price_buy' => 195.00, 'price_sell' => 380.00,
                'quantity_on_hand' => 4, 'low_stock_threshold' => 2,
            ],
            // Batteries
            [
                'category' => 'autre', 'name' => 'Batterie 12V 60Ah Varta',
                'sku' => 'BAT-VARTA-60AH', 'unit' => 'unite',
                'price_buy' => 360.00, 'price_sell' => 580.00,
                'quantity_on_hand' => 5, 'low_stock_threshold' => 2,
            ],
            [
                'category' => 'autre', 'name' => 'Batterie 12V 70Ah Bosch',
                'sku' => 'BAT-BOSCH-70AH', 'unit' => 'unite',
                'price_buy' => 430.00, 'price_sell' => 680.00,
                'quantity_on_hand' => 4, 'low_stock_threshold' => 2,
            ],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(['sku' => $product['sku']], $product);
        }
    }
}
