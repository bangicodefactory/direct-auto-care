<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $admin    = User::role('admin')->first();
        $products = Product::all()->keyBy('sku');

        $invoices = [
            // Devis 1 — vidange + filtre huile, converti en facture
            [
                'invoice' => [
                    'type'       => InvoiceType::Quote->value,
                    'number'     => 'DEV-2026-0001',
                    'issue_date' => '2026-03-10',
                    'due_date'   => '2026-04-09',
                    'fiscal_year' => 2026,
                    'status'     => InvoiceStatus::Issued->value,
                    'notes'      => 'Devis vidange moteur + filtre huile.',
                ],
                'items' => [
                    ['sku' => 'HU-5W30-5L',     'description' => 'Huile moteur 5W-30 5L',       'qty' => 1, 'unit_price' => 230.00],
                    ['sku' => 'FH-MANN-W71275',  'description' => 'Filtre à huile MANN W712/75', 'qty' => 1, 'unit_price' => 45.00],
                    ['sku' => null,              'description' => 'Main d\'œuvre vidange',       'qty' => 1, 'unit_price' => 80.00],
                ],
            ],
            // Facture 1 — conversion du devis 1
            [
                'invoice' => [
                    'type'       => InvoiceType::Invoice->value,
                    'number'     => 'FAC-2026-0001',
                    'issue_date' => '2026-03-12',
                    'due_date'   => '2026-04-11',
                    'fiscal_year' => 2026,
                    'status'     => InvoiceStatus::Paid->value,
                    'notes'      => null,
                ],
                'items' => [
                    ['sku' => 'HU-5W30-5L',     'description' => 'Huile moteur 5W-30 5L',       'qty' => 1, 'unit_price' => 230.00],
                    ['sku' => 'FH-MANN-W71275',  'description' => 'Filtre à huile MANN W712/75', 'qty' => 1, 'unit_price' => 45.00],
                    ['sku' => null,              'description' => 'Main d\'œuvre vidange',       'qty' => 1, 'unit_price' => 80.00],
                ],
                'converted_from' => 'DEV-2026-0001',
            ],
            // Facture 2 — révision complète + plaquettes
            [
                'invoice' => [
                    'type'       => InvoiceType::Invoice->value,
                    'number'     => 'FAC-2026-0002',
                    'issue_date' => '2026-04-05',
                    'due_date'   => '2026-05-05',
                    'fiscal_year' => 2026,
                    'status'     => InvoiceStatus::Paid->value,
                    'notes'      => null,
                ],
                'items' => [
                    ['sku' => 'HU-10W40-5L',    'description' => 'Huile moteur 10W-40 5L',       'qty' => 1, 'unit_price' => 195.00],
                    ['sku' => 'FH-BOSCH-P7051',  'description' => 'Filtre à huile Bosch P7051',   'qty' => 1, 'unit_price' => 50.00],
                    ['sku' => 'FA-MANN-C24004',  'description' => 'Filtre à air MANN C24004',     'qty' => 1, 'unit_price' => 75.00],
                    ['sku' => 'FC-MANN-CU2442',  'description' => "Filtre d'habitacle MANN CU2442", 'qty' => 1, 'unit_price' => 60.00],
                    ['sku' => 'PF-BREMBO-AV',    'description' => 'Plaquettes de frein avant Brembo', 'qty' => 1, 'unit_price' => 220.00],
                    ['sku' => null,              'description' => 'Main d\'œuvre révision complète', 'qty' => 1, 'unit_price' => 250.00],
                ],
            ],
            // Devis 2 — remplacement batterie (brouillon)
            [
                'invoice' => [
                    'type'       => InvoiceType::Quote->value,
                    'number'     => 'DEV-2026-0002',
                    'issue_date' => '2026-05-10',
                    'due_date'   => '2026-06-09',
                    'fiscal_year' => 2026,
                    'status'     => InvoiceStatus::Draft->value,
                    'notes'      => 'Batterie défaillante — à confirmer avec le client.',
                ],
                'items' => [
                    ['sku' => 'BAT-VARTA-60AH', 'description' => 'Batterie 12V 60Ah Varta', 'qty' => 1, 'unit_price' => 580.00],
                    ['sku' => null,             'description' => 'Pose batterie',            'qty' => 1, 'unit_price' => 50.00],
                ],
            ],
            // Facture 3 — disques et plaquettes arrière (impayée)
            [
                'invoice' => [
                    'type'       => InvoiceType::Invoice->value,
                    'number'     => 'FAC-2026-0003',
                    'issue_date' => '2026-04-20',
                    'due_date'   => '2026-05-20',
                    'fiscal_year' => 2026,
                    'status'     => InvoiceStatus::Issued->value,
                    'notes'      => null,
                ],
                'items' => [
                    ['sku' => 'DF-AV-PAIRE',   'description' => 'Disques de frein avant (paire)', 'qty' => 1, 'unit_price' => 380.00],
                    ['sku' => 'PF-BREMBO-AR',  'description' => 'Plaquettes de frein arrière Brembo', 'qty' => 1, 'unit_price' => 175.00],
                    ['sku' => null,            'description' => 'Main d\'œuvre freinage',         'qty' => 1, 'unit_price' => 150.00],
                ],
            ],
        ];

        $customers = Customer::all();
        $vehicles  = Vehicle::all();
        $created   = [];

        foreach ($invoices as $data) {
            $customer  = $customers->random();
            $vehicle   = $vehicles->where('customer_id', $customer->id)->first()
                ?? $vehicles->random();

            $items        = $data['items'];
            $subtotalHt   = collect($items)->sum(fn ($i) => $i['qty'] * $i['unit_price']);
            $discount     = 0.00;
            $tvaRate      = 20.00;
            $tvaAmount    = round(($subtotalHt - $discount) * $tvaRate / 100, 2);
            $totalTtc     = round($subtotalHt - $discount + $tvaAmount, 2);

            $convertedFromId = null;
            if (isset($data['converted_from'])) {
                $convertedFromId = $created[$data['converted_from']]?->id ?? null;
            }

            $invoice = Invoice::create(array_merge($data['invoice'], [
                'customer_id'             => $customer->id,
                'vehicle_id'              => $vehicle->id,
                'subtotal_ht'             => $subtotalHt,
                'discount'                => $discount,
                'tva_rate'                => $tvaRate,
                'tva_amount'              => $tvaAmount,
                'total_ttc'               => $totalTtc,
                'created_by'              => $admin->id,
                'converted_from_quote_id' => $convertedFromId,
            ]));

            foreach ($items as $item) {
                $product   = $item['sku'] ? $products->get($item['sku']) : null;
                $lineTotal = round($item['qty'] * $item['unit_price'], 2);

                InvoiceItem::create([
                    'invoice_id'    => $invoice->id,
                    'product_id'    => $product?->id,
                    'description'   => $item['description'],
                    'quantity'      => $item['qty'],
                    'unit_price_ht' => $item['unit_price'],
                    'line_total_ht' => $lineTotal,
                ]);
            }

            $created[$invoice->number] = $invoice;
        }
    }
}
