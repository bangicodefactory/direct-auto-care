<?php

namespace Tests\Feature;

use App\Enums\CustomerType;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class CustomerModelTest extends TestCase
{
    public function test_display_name_uses_company_name_for_company(): void
    {
        $customer = new Customer();
        $customer->type = CustomerType::Company;
        $customer->company_name = 'Transports Atlas SARL';
        $customer->first_name = 'Youssef';
        $customer->last_name = 'Alaoui';

        $this->assertSame('Transports Atlas SARL', $customer->display_name);
    }

    public function test_display_name_uses_full_name_for_individual(): void
    {
        $customer = new Customer();
        $customer->type = CustomerType::Individual;
        $customer->first_name = 'Youssef';
        $customer->last_name = 'Alaoui';

        $this->assertSame('Youssef Alaoui', $customer->display_name);
    }

    public function test_display_name_falls_back_to_contact_when_company_name_missing(): void
    {
        $customer = new Customer();
        $customer->type = CustomerType::Company;
        $customer->company_name = null;
        $customer->first_name = 'Youssef';
        $customer->last_name = 'Alaoui';

        $this->assertSame('Youssef Alaoui', $customer->display_name);
    }

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
        $relation = (new Customer())->vehicles();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Vehicle::class, $relation->getRelated());
    }

    public function test_appointments_returns_has_many(): void
    {
        $relation = (new Customer())->appointments();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Appointment::class, $relation->getRelated());
    }

    public function test_invoices_returns_has_many(): void
    {
        $relation = (new Customer())->invoices();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Invoice::class, $relation->getRelated());
    }
}
