<?php

namespace Tests\Unit\Services;

use App\Enums\InvoiceType;
use App\Services\InvoiceNumberingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoiceNumberingServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceNumberingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoiceNumberingService();
    }

    // Helper — service must be called inside a transaction.
    private function next(InvoiceType $type, int $fiscalYear = 2026): string
    {
        return DB::transaction(fn () => $this->service->next($type, $fiscalYear));
    }

    // Helper — seed the sequence table to simulate previous invoices.
    private function seedSequence(InvoiceType $type, int $fiscalYear, int $lastSequence): void
    {
        DB::table('invoice_sequences')->insert([
            'type'          => $type->value,
            'fiscal_year'   => $fiscalYear,
            'last_sequence' => $lastSequence,
        ]);
    }

    // -------------------------------------------------------------------------
    // Prefixes
    // -------------------------------------------------------------------------

    public function test_uses_fac_prefix_for_invoice(): void
    {
        $this->assertStringStartsWith('FAC-', $this->next(InvoiceType::Invoice));
    }

    public function test_uses_dev_prefix_for_quote(): void
    {
        $this->assertStringStartsWith('DEV-', $this->next(InvoiceType::Quote));
    }

    // -------------------------------------------------------------------------
    // First number in empty table
    // -------------------------------------------------------------------------

    public function test_first_invoice_number_is_one(): void
    {
        $this->assertSame('FAC-2026-00001', $this->next(InvoiceType::Invoice));
    }

    public function test_first_quote_number_is_one(): void
    {
        $this->assertSame('DEV-2026-00001', $this->next(InvoiceType::Quote));
    }

    // -------------------------------------------------------------------------
    // Increment from existing sequence
    // -------------------------------------------------------------------------

    public function test_increments_from_existing_sequence(): void
    {
        $this->seedSequence(InvoiceType::Invoice, 2026, 5);

        $this->assertSame('FAC-2026-00006', $this->next(InvoiceType::Invoice));
    }

    // -------------------------------------------------------------------------
    // Isolation across types and fiscal years
    // -------------------------------------------------------------------------

    public function test_invoice_and_quote_sequences_are_independent(): void
    {
        $this->seedSequence(InvoiceType::Invoice, 2026, 3);

        $this->assertSame('DEV-2026-00001', $this->next(InvoiceType::Quote));
    }

    public function test_sequences_are_independent_by_fiscal_year(): void
    {
        $this->seedSequence(InvoiceType::Invoice, 2025, 10);

        $this->assertSame('FAC-2026-00001', $this->next(InvoiceType::Invoice, 2026));
    }

    // -------------------------------------------------------------------------
    // Two consecutive calls
    // -------------------------------------------------------------------------

    public function test_two_consecutive_calls_increment_monotonically(): void
    {
        $first  = $this->next(InvoiceType::Invoice);
        $second = $this->next(InvoiceType::Invoice);

        $this->assertSame('FAC-2026-00001', $first);
        $this->assertSame('FAC-2026-00002', $second);
    }

    // -------------------------------------------------------------------------
    // Format contract
    // -------------------------------------------------------------------------

    public function test_number_matches_format_pattern(): void
    {
        $this->assertMatchesRegularExpression('/^FAC-\d{4}-\d{5}$/', $this->next(InvoiceType::Invoice));
        $this->assertMatchesRegularExpression('/^DEV-\d{4}-\d{5}$/', $this->next(InvoiceType::Quote));
    }
}
