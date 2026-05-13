<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class CustomerModelTest extends TestCase
{
    public function test_phone_normalized_from_local_format(): void
    {
        $customer = new Customer();
        $customer->phone = '0612345678';

        $this->assertSame('+212612345678', $customer->phone);
    }

    public function test_phone_already_normalized_is_unchanged(): void
    {
        $customer = new Customer();
        $customer->phone = '+212612345678';

        $this->assertSame('+212612345678', $customer->phone);
    }

    public function test_phone_normalized_from_double_zero_prefix(): void
    {
        $customer = new Customer();
        $customer->phone = '00212612345678';

        $this->assertSame('+212612345678', $customer->phone);
    }

    public function test_phone_spaces_and_dashes_stripped_before_normalizing(): void
    {
        $customer = new Customer();
        $customer->phone = '06 12-34 56.78';

        $this->assertSame('+212612345678', $customer->phone);
    }

    public function test_vehicles_returns_has_many(): void
    {
        $customer = new Customer();

        $this->assertInstanceOf(HasMany::class, $customer->vehicles());
        $this->assertInstanceOf(Vehicle::class, $customer->vehicles()->getRelated());
    }

    public function test_appointments_returns_has_many(): void
    {
        $customer = new Customer();

        $this->assertInstanceOf(HasMany::class, $customer->appointments());
        $this->assertInstanceOf(Appointment::class, $customer->appointments()->getRelated());
    }

    public function test_invoices_returns_has_many(): void
    {
        $customer = new Customer();

        $this->assertInstanceOf(HasMany::class, $customer->invoices());
        $this->assertInstanceOf(Invoice::class, $customer->invoices()->getRelated());
    }
}
