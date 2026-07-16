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

    public function test_print_view_shows_company_fiscal_identity(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');
        $invoice = Invoice::factory()
            ->for(Customer::factory()->company()->state([
                'company_name' => 'Transports Atlas SARL',
                'ice'          => '001234567000089',
                'if'           => '19283746',
                'patente'      => '87654321',
            ]), 'customer')
            ->create();

        $this->actingAs($employee)
            ->get(route('invoices.print', $invoice))
            ->assertOk()
            ->assertViewIs('pdf.invoice')
            ->assertSee('Transports Atlas SARL')   // raison sociale as the bill-to name
            ->assertSee('001234567000089')         // ICE — required on a B2B invoice
            ->assertSee('19283746')                // IF
            ->assertSee('87654321');               // Patente
    }

    public function test_print_view_hides_fiscal_ids_for_individual(): void
    {
        // Silence the SELLER (garage) fiscal lines so the assertion below is purely
        // about the BUYER block being gated on customer type, not the header.
        config(['company.ice' => '', 'company.if' => '', 'company.rc' => '']);

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
            ->assertDontSee('ICE :');
    }

    // -------------------------------------------------------------------------
    // Per-line vehicle column (multi-vehicle invoices)
    // -------------------------------------------------------------------------

    public function test_print_view_shows_vehicle_column_when_lines_are_attributed(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $customer = Customer::factory()->create();
        $vehicle  = \App\Models\Vehicle::factory()->create([
            'customer_id'   => $customer->id,
            'license_plate' => '12345-A-6',
            'make'          => 'Dacia',
            'model'         => 'Logan',
        ]);
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);
        $invoice->items()->create([
            'product_id'    => null,
            'vehicle_id'    => $vehicle->id,
            'description'   => 'Vidange moteur',
            'quantity'      => 1,
            'unit_price_ht' => 150,
            'line_total_ht' => 150,
        ]);

        $this->actingAs($employee)
            ->get(route('invoices.print', $invoice))
            ->assertOk()
            ->assertSee('Véhicule')       // the per-line column header
            ->assertSee('12345-A-6')      // plate on the attributed line
            ->assertSee('Dacia');
    }

    public function test_print_view_hides_vehicle_column_without_line_attribution(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $customer = Customer::factory()->create();
        $invoice  = Invoice::factory()->create(['customer_id' => $customer->id]);
        $invoice->items()->create([
            'product_id'    => null,
            'vehicle_id'    => null,
            'description'   => "Main d'œuvre",
            'quantity'      => 1,
            'unit_price_ht' => 200,
            'line_total_ht' => 200,
        ]);

        $this->actingAs($employee)
            ->get(route('invoices.print', $invoice))
            ->assertOk()
            ->assertSee("Main d'œuvre")
            ->assertDontSee('<th class="col-veh text-left">Véhicule</th>', false);
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
