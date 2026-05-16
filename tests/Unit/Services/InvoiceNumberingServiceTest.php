<?php

namespace Tests\Unit\Services;

use App\Enums\InvoiceType;
use App\Models\Invoice;
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
    // Increment from existing max
    // -------------------------------------------------------------------------

    public function test_increments_from_existing_max(): void
    {
        Invoice::factory()->create([
            'type'        => InvoiceType::Invoice->value,
            'fiscal_year' => 2026,
            'number'      => 'FAC-2026-00005',
        ]);

        $this->assertSame('FAC-2026-00006', $this->next(InvoiceType::Invoice));
    }

    // -------------------------------------------------------------------------
    // Isolation across types and fiscal years
    // -------------------------------------------------------------------------

    public function test_invoice_and_quote_sequences_are_independent(): void
    {
        Invoice::factory()->create([
            'type'        => InvoiceType::Invoice->value,
            'fiscal_year' => 2026,
            'number'      => 'FAC-2026-00003',
        ]);

        $this->assertSame('DEV-2026-00001', $this->next(InvoiceType::Quote));
    }

    public function test_sequences_are_independent_by_fiscal_year(): void
    {
        Invoice::factory()->create([
            'type'        => InvoiceType::Invoice->value,
            'fiscal_year' => 2025,
            'number'      => 'FAC-2025-00010',
        ]);

        $this->assertSame('FAC-2026-00001', $this->next(InvoiceType::Invoice, 2026));
    }

    // -------------------------------------------------------------------------
    // Soft-deleted rows must still count
    // -------------------------------------------------------------------------

    public function test_includes_soft_deleted_invoices_in_sequence(): void
    {
        $invoice = Invoice::factory()->create([
            'type'        => InvoiceType::Invoice->value,
            'fiscal_year' => 2026,
            'number'      => 'FAC-2026-00007',
        ]);
        $invoice->delete();

        $this->assertSame('FAC-2026-00008', $this->next(InvoiceType::Invoice));
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
