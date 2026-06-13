<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // -------------------------------------------------------------------------
    // PDF route
    // -------------------------------------------------------------------------

    public function test_guest_is_redirected_from_pdf_route(): void
    {
        $invoice = Invoice::factory()->create();

        $this->get(route('invoices.pdf', $invoice))
            ->assertRedirect(route('login'));
    }

    public function test_employee_can_download_pdf(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $invoice = Invoice::factory()->create();

        $response = $this->actingAs($employee)
            ->get(route('invoices.pdf', $invoice));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_admin_can_download_pdf(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $invoice = Invoice::factory()->create();

        $response = $this->actingAs($admin)
            ->get(route('invoices.pdf', $invoice));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pdf_filename_matches_invoice_number(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $invoice = Invoice::factory()->create();

        $response = $this->actingAs($employee)
            ->get(route('invoices.pdf', $invoice));

        $response->assertOk();
        $this->assertStringContainsString(
            $invoice->number,
            $response->headers->get('Content-Disposition') ?? ''
        );
    }

    // -------------------------------------------------------------------------
    // Print route
    // -------------------------------------------------------------------------

    public function test_guest_is_redirected_from_print_route(): void
    {
        $invoice = Invoice::factory()->create();

        $this->get(route('invoices.print', $invoice))
            ->assertRedirect(route('login'));
    }

    public function test_employee_can_access_print_view(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $invoice = Invoice::factory()->create();

        $this->actingAs($employee)
            ->get(route('invoices.print', $invoice))
            ->assertOk()
            ->assertViewIs('pdf.invoice')
            ->assertSee($invoice->number);
    }

    public function test_admin_can_access_print_view(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $invoice = Invoice::factory()->create();

        $this->actingAs($admin)
            ->get(route('invoices.print', $invoice))
            ->assertOk()
            ->assertViewIs('pdf.invoice');
    }

    public function test_print_view_contains_customer_info(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $invoice = Invoice::factory()
            ->for(Customer::factory()->state([
                'first_name' => 'Youssef',
                'last_name'  => 'Alaoui',
            ]), 'customer')
            ->create();

        $this->actingAs($employee)
            ->get(route('invoices.print', $invoice))
            ->assertOk()
            ->assertSee('Youssef')
            ->assertSee('Alaoui');
    }

    public function test_print_view_shows_ice_for_a_company_customer(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $invoice = Invoice::factory()
            ->for(Customer::factory()->company()->state(['ice' => '001234567000089']), 'customer')
            ->create(['payment_method' => 'transfer']);

        $this->actingAs($employee)
            ->get(route('invoices.print', $invoice))
            ->assertOk()
            ->assertSee('001234567000089')
            ->assertSee('Mode de règlement')
            ->assertSee('Virement');
    }

    public function test_print_view_hides_client_ice_for_an_individual_customer(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        // Even with an ICE stored, an individual's ICE must not render (isCompany() gates it).
        $invoice = Invoice::factory()
            ->for(Customer::factory()->state(['type' => 'individual', 'ice' => '009999999000011']), 'customer')
            ->create();

        $this->actingAs($employee)
            ->get(route('invoices.print', $invoice))
            ->assertOk()
            ->assertDontSee('009999999000011');
    }

    public function test_user_without_role_cannot_access_pdf(): void
    {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->create();

        $this->actingAs($user)
            ->get(route('invoices.pdf', $invoice))
            ->assertForbidden();
    }

    public function test_user_without_role_cannot_access_print(): void
    {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->create();

        $this->actingAs($user)
            ->get(route('invoices.print', $invoice))
            ->assertForbidden();
    }
}
