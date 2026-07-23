<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Deleting the LAST document of a numbering series rolls the counter back so
 * the number is reused (the trashed row is renamed to free the unique slot).
 * Mid-sequence deletions release nothing — the gap is a normal fiscal gap.
 * Real DB row locks per CLAUDE.md — documents are created through the store
 * endpoint so numbering runs exactly as in production.
 */
class InvoiceNumberReleaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    private function storeDocument(User $user, string $type = 'invoice'): Invoice
    {
        $customer = Customer::factory()->create();

        $this->actingAs($user)->post(route('invoices.store'), [
            'type'        => $type,
            'customer_id' => $customer->id,
            'issue_date'  => now()->toDateString(),
            'discount'    => 0,
            'tva_rate'    => 20,
            'items'       => [[
                'description'   => "Main d'œuvre",
                'quantity'      => 1,
                'unit_price_ht' => 100,
            ]],
        ])->assertRedirect();

        return Invoice::latest('id')->firstOrFail();
    }

    public function test_deleting_the_tail_facture_reuses_its_number(): void
    {
        $admin = $this->admin();
        $first = $this->storeDocument($admin);

        $this->assertStringEndsWith('-00001', $first->number);

        $this->actingAs($admin)->delete(route('invoices.destroy', $first));

        // The trashed row freed the slot…
        $this->assertStringContainsString('#', $first->refresh()->number);
        // …and the next facture reuses the released number.
        $second = $this->storeDocument($admin);
        $this->assertStringEndsWith('-00001', $second->number);
    }

    public function test_deleting_a_mid_sequence_facture_leaves_the_counter_alone(): void
    {
        $admin  = $this->admin();
        $first  = $this->storeDocument($admin);
        $second = $this->storeDocument($admin);

        $this->assertStringEndsWith('-00002', $second->number);

        $this->actingAs($admin)->delete(route('invoices.destroy', $first));

        // Number not freed (kept, gap remains), counter untouched → next is 00003.
        $this->assertSame($first->number, $first->refresh()->number);
        $third = $this->storeDocument($admin);
        $this->assertStringEndsWith('-00003', $third->number);
    }

    public function test_devis_tail_number_is_released_independently(): void
    {
        $admin = $this->admin();
        $this->storeDocument($admin); // FAC-…-00001 — separate series
        $devis = $this->storeDocument($admin, 'quote');

        $this->assertStringEndsWith('-00001', $devis->number);
        $this->assertStringStartsWith('DEV-', $devis->number);

        $this->actingAs($admin)->delete(route('invoices.destroy', $devis));

        $newDevis = $this->storeDocument($admin, 'quote');
        $this->assertStringEndsWith('-00001', $newDevis->number);
        // The facture series was never touched.
        $facture = $this->storeDocument($admin);
        $this->assertStringEndsWith('-00002', $facture->number);
    }

    public function test_cancelling_does_not_release_the_number(): void
    {
        $admin   = $this->admin();
        $invoice = $this->storeDocument($admin);

        $this->actingAs($admin)->post(route('invoices.cancel', $invoice));

        // A cancelled document keeps its number in the fiscal sequence.
        $this->assertStringEndsWith('-00001', $invoice->refresh()->number);
        $next = $this->storeDocument($admin);
        $this->assertStringEndsWith('-00002', $next->number);
    }

    public function test_deleting_a_cancelled_tail_facture_still_releases_the_number(): void
    {
        $admin   = $this->admin();
        $invoice = $this->storeDocument($admin);

        $this->actingAs($admin)->post(route('invoices.cancel', $invoice));
        $this->actingAs($admin)->delete(route('invoices.destroy', $invoice));

        $next = $this->storeDocument($admin);
        $this->assertStringEndsWith('-00001', $next->number);
    }
}
