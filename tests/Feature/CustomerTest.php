<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_be_soft_deleted(): void
    {
        $customer = Customer::factory()->create();

        $customer->delete();

        $this->assertSoftDeleted($customer);
        $this->assertNotNull(Customer::withTrashed()->find($customer->id));
    }

    public function test_customer_fillable_fields_are_correct(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'Youssef',
            'last_name' => 'El Amrani',
            'phone' => '+212612345678',
            'email' => 'youssef@example.com',
            'address' => 'Rue Mohammed V, Casablanca',
            'notes' => 'Client fidèle.',
            'notify_email' => true,
            'notify_whatsapp' => false,
        ]);

        $this->assertSame('Youssef', $customer->first_name);
        $this->assertSame('El Amrani', $customer->last_name);
        $this->assertSame('+212612345678', $customer->phone);
        $this->assertTrue($customer->notify_email);
        $this->assertFalse($customer->notify_whatsapp);
    }

    public function test_notify_flags_are_cast_to_boolean(): void
    {
        $customer = Customer::factory()->create([
            'notify_email' => true,
            'notify_whatsapp' => false,
        ]);

        $this->assertIsBool($customer->notify_email);
        $this->assertIsBool($customer->notify_whatsapp);
    }

    public function test_phone_is_unique(): void
    {
        Customer::factory()->create(['phone' => '+212611111111']);

        $this->assertDatabaseCount('customers', 1);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Customer::factory()->create(['phone' => '+212611111111']);
    }

    public function test_email_is_unique(): void
    {
        Customer::factory()->create(['email' => 'test@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Customer::factory()->create(['email' => 'test@example.com']);
    }

    public function test_email_and_address_and_notes_are_nullable(): void
    {
        $customer = Customer::factory()->create([
            'email' => null,
            'address' => null,
            'notes' => null,
        ]);

        $this->assertNull($customer->email);
        $this->assertNull($customer->address);
        $this->assertNull($customer->notes);
    }

    public function test_seeder_creates_twenty_customers(): void
    {
        $this->seed(\Database\Seeders\CustomerSeeder::class);

        $this->assertDatabaseCount('customers', 20);
    }
}
