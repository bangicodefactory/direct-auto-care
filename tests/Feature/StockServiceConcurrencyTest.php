<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * Uses DatabaseMigrations (not RefreshDatabase) so product/user rows are
 * committed to the DB and visible to the worker subprocesses that each open
 * their own connection.  Run against a dedicated test database.
 */
#[Group('concurrency')]
class StockServiceConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_concurrent_out_movements_never_leave_quantity_negative(): void
    {
        $product = Product::factory()->create(['quantity_on_hand' => 3]);
        $user    = User::factory()->create();

        $autoload  = addslashes(base_path('vendor/autoload.php'));
        $bootstrap = addslashes(base_path('bootstrap/app.php'));

        $workerScript = <<<PHP
        <?php
        require '{$autoload}';
        \$app = require '{$bootstrap}';
        \$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
        \$kernel->bootstrap();

        use App\Enums\StockMovementType;
        use App\Exceptions\InsufficientStockException;
        use App\Models\Product;
        use App\Models\User;
        use App\Services\StockService;

        \$product = Product::findOrFail((int) \$argv[1]);
        \$user    = User::findOrFail((int) \$argv[2]);

        try {
            (new StockService())->recordMovement(
                \$product, StockMovementType::Out, 1, null, null, \$user
            );
            echo 'ok';
        } catch (InsufficientStockException) {
            echo 'insufficient';
        }
        PHP;

        $workerFile = tempnam(sys_get_temp_dir(), 'stock_worker_') . '.php';
        file_put_contents($workerFile, $workerScript);

        $handles = [];
        $pipes   = [];
        $phpBin  = PHP_BINARY;

        // Spawn 5 workers simultaneously — quantity is 3, so exactly 3 should succeed
        for ($i = 0; $i < 5; $i++) {
            $handles[$i] = proc_open(
                "{$phpBin} {$workerFile} {$product->id} {$user->id}",
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes[$i]
            );
        }

        $results = [];
        foreach ($handles as $i => $handle) {
            $results[] = trim(stream_get_contents($pipes[$i][1]));
            fclose($pipes[$i][1]);
            fclose($pipes[$i][2]);
            proc_close($handle);
        }

        try {
            $successes    = count(array_filter($results, fn ($r) => $r === 'ok'));
            $insufficient = count(array_filter($results, fn ($r) => $r === 'insufficient'));
            $debug        = implode(', ', $results);

            $this->assertCount(5, $results, "Expected 5 worker outputs, got unexpected crashes. Results: {$debug}");
            $this->assertEquals(3, $successes, "Expected exactly 3 successes. Results: {$debug}");
            $this->assertEquals(2, $insufficient);
            $this->assertEquals(0, $product->fresh()->quantity_on_hand);
        } finally {
            @unlink($workerFile);
        }
    }
}
