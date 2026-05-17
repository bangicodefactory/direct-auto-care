<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * IST-57 — Numbering integrity under concurrent invoice creation.
 *
 * Uses DatabaseMigrations (not RefreshDatabase) so Customer/User rows committed
 * by this test are visible to the worker subprocesses that open their own DB
 * connections. Run against a dedicated test database.
 *
 * The InvoiceNumberingService uses INSERT … ON DUPLICATE KEY UPDATE which
 * acquires an exclusive X-lock on the invoice_sequences row, preventing any
 * concurrent transaction from reading or modifying it until the lock holder
 * commits. This test verifies that guarantee end-to-end: N workers racing to
 * create invoices must each receive a unique, sequential number.
 */
#[Group('concurrency')]
class InvoiceNumberingConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_concurrent_invoice_creates_produce_unique_sequential_numbers(): void
    {
        $customer = Customer::factory()->create();
        $user     = User::factory()->create();

        $autoload  = addslashes(base_path('vendor/autoload.php'));
        $bootstrap = addslashes(base_path('bootstrap/app.php'));
        $year      = now()->year;

        $workerScript = <<<PHP
        <?php
        require '{$autoload}';
        \$app = require '{$bootstrap}';
        \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
        \$kernel->bootstrap();

        use App\Models\Customer;
        use App\Models\User;
        use App\Services\InvoiceService;

        \$customer = Customer::findOrFail((int) \$argv[1]);
        \$user     = User::findOrFail((int) \$argv[2]);

        \$invoice = app(InvoiceService::class)->create([
            'type'        => 'invoice',
            'customer_id' => \$customer->id,
            'issue_date'  => '{$year}-01-01',
            'discount'    => 0,
            'tva_rate'    => 20,
            'items'       => [['description' => 'Service atelier', 'quantity' => 1, 'unit_price_ht' => 100]],
        ], \$user);

        echo \$invoice->number;
        PHP;

        // Avoid tempnam() because it creates an extension-less file that leaks on cleanup.
        $workerFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'inv_numbering_worker_' . uniqid() . '.php';
        file_put_contents($workerFile, $workerScript);

        $handles = [];
        $pipes   = [];
        $phpBin  = PHP_BINARY;
        $workers = 8;

        // Spawn all workers simultaneously; quote paths to handle spaces in directory names.
        for ($i = 0; $i < $workers; $i++) {
            $handles[$i] = proc_open(
                "\"{$phpBin}\" \"{$workerFile}\" {$customer->id} {$user->id}",
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes[$i]
            );
        }

        $numbers = [];
        $errors  = [];
        foreach ($handles as $i => $handle) {
            $stdout   = trim(stream_get_contents($pipes[$i][1]));
            $stderr   = trim(stream_get_contents($pipes[$i][2]));
            fclose($pipes[$i][1]);
            fclose($pipes[$i][2]);
            proc_close($handle);

            if ($stdout !== '') {
                $numbers[] = $stdout;
            }
            if ($stderr !== '') {
                $errors[] = "Worker {$i}: {$stderr}";
            }
        }

        try {
            $debug = implode(', ', $numbers);

            $this->assertCount(
                $workers,
                $numbers,
                "All {$workers} workers must produce a number (no crashes). Got: {$debug}. Errors: " . implode(' | ', $errors)
            );

            // No duplicates
            $unique = array_unique($numbers);
            $this->assertCount($workers, $unique, "Duplicate numbers detected: {$debug}");

            // All follow the FAC-YYYY-NNNNN pattern
            foreach ($numbers as $number) {
                $this->assertMatchesRegularExpression('/^FAC-\d{4}-\d{5}$/', $number);
            }

            // Sequences must form a gap-free set {1, 2, …, N} (order varies between workers)
            $sequences = array_map(fn ($n) => (int) substr($n, -5), $numbers);
            sort($sequences);
            $this->assertEquals(
                range(1, $workers),
                $sequences,
                "Numbers must form a gap-free sequence 1…{$workers}. Got: {$debug}"
            );
        } finally {
            @unlink($workerFile);
        }
    }
}
