<?php

namespace Tests\Feature\Services;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class InvoiceNumberingServiceConcurrencyTest extends TestCase
{
    // DatabaseMigrations (migrate:fresh) gives us committed rows that are visible
    // across separate DB connections spawned by the subprocesses.
    // RefreshDatabase wraps each test in a transaction, which would hide committed
    // rows from other connections — that would break the concurrency test entirely.
    use DatabaseMigrations;

    // Worker script run by each subprocess.
    // Written as a nowdoc so $argv / $basePath are not interpolated here.
    private const WORKER = <<<'PHP'
        <?php
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';
        $basePath = $argv[1];
        require $basePath . '/vendor/autoload.php';
        $app = require_once $basePath . '/bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        use App\Enums\InvoiceType;
        use App\Models\Invoice;
        use App\Services\InvoiceNumberingService;
        use Illuminate\Support\Facades\DB;
        $customerId = (int) $argv[2];
        $userId     = (int) $argv[3];
        $fiscalYear = (int) $argv[4];
        // retry=3 handles rare InnoDB gap-lock false-deadlocks
        echo DB::transaction(function () use ($customerId, $userId, $fiscalYear): string {
            $number = app(InvoiceNumberingService::class)->next(InvoiceType::Invoice, $fiscalYear);
            Invoice::create([
                'type'        => 'invoice',
                'number'      => $number,
                'customer_id' => $customerId,
                'fiscal_year' => $fiscalYear,
                'issue_date'  => now()->toDateString(),
                'subtotal_ht' => 100,
                'discount'    => 0,
                'tva_rate'    => 20,
                'tva_amount'  => 20,
                'total_ttc'   => 120,
                'status'      => 'draft',
                'created_by'  => $userId,
            ]);
            return $number;
        }, 3);
        PHP;

    public function test_ten_concurrent_creates_produce_no_duplicates_and_no_gaps(): void
    {
        $customerId = Customer::factory()->create()->id;
        $userId     = User::factory()->create()->id;
        $fiscalYear = (int) now()->format('Y');
        $projectRoot = base_path();

        $scriptPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'inv_numbering_worker.php';
        file_put_contents($scriptPath, self::WORKER);

        try {
            $processes = [];
            $pipes     = [];

            // Start all 10 processes simultaneously before reading any output.
            for ($i = 0; $i < 10; $i++) {
                $descriptors = [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ];
                $cmd = implode(' ', [
                    escapeshellarg(PHP_BINARY),
                    escapeshellarg($scriptPath),
                    escapeshellarg($projectRoot),
                    $customerId,
                    $userId,
                    $fiscalYear,
                ]);

                $proc = proc_open($cmd, $descriptors, $pipes[$i], $projectRoot);
                $this->assertIsResource($proc, "Failed to start subprocess {$i}");
                fclose($pipes[$i][0]);
                $processes[$i] = $proc;
            }

            $numbers = [];
            $errors  = [];

            for ($i = 0; $i < 10; $i++) {
                $output = trim(stream_get_contents($pipes[$i][1]));
                $stderr = trim(stream_get_contents($pipes[$i][2]));
                proc_close($processes[$i]);

                if ($stderr !== '') {
                    $errors[] = "Subprocess {$i}: {$stderr}";
                }
                if ($output !== '') {
                    $numbers[] = $output;
                }
            }
        } finally {
            @unlink($scriptPath);
        }

        if ($errors !== []) {
            $this->fail("One or more subprocesses failed:\n" . implode("\n", $errors));
        }

        $this->assertCount(10, $numbers, 'Expected 10 invoice numbers; some subprocesses produced no output.');

        $unique = array_unique($numbers);
        $this->assertCount(10, $unique, 'Duplicate numbers detected — SELECT … FOR UPDATE locking failed.');

        sort($numbers);
        $expected = array_map(fn (int $n) => sprintf('FAC-%d-%05d', $fiscalYear, $n), range(1, 10));
        $this->assertEquals($expected, $numbers, 'Numbers are not strictly sequential — gap detected.');
    }
}
