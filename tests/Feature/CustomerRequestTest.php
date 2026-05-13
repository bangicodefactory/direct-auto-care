<?php

namespace Tests\Feature;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CustomerRequestTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function callPrepare(FormRequest $request): void
    {
        $method = new \ReflectionMethod($request, 'prepareForValidation');
        $method->invoke($request);
    }

    private function makeUpdateRequest(Customer $customer): UpdateCustomerRequest
    {
        $request = new UpdateCustomerRequest();
        $request->setRouteResolver(function () use ($customer) {
            return new class ($customer) {
                public function __construct(private Customer $customer)
                {
                }

                public function parameter(string $key, mixed $default = null): mixed
                {
                    return $key === 'customer' ? $this->customer : $default;
                }
            };
        });

        return $request;
    }

    // -------------------------------------------------------------------------
    // prepareForValidation — phone normalization (Store)
    // -------------------------------------------------------------------------

    public function test_local_phone_is_normalized_before_validation(): void
    {
        $request = new StoreCustomerRequest();
        $request->merge(['phone' => '0612345678']);
        $this->callPrepare($request);

        $this->assertSame('+212612345678', $request->phone);
    }

    public function test_double_zero_phone_is_normalized_before_validation(): void
    {
        $request = new StoreCustomerRequest();
        $request->merge(['phone' => '00212612345678']);
        $this->callPrepare($request);

        $this->assertSame('+212612345678', $request->phone);
    }

    public function test_already_normalized_phone_is_unchanged(): void
    {
        $request = new StoreCustomerRequest();
        $request->merge(['phone' => '+212612345678']);
        $this->callPrepare($request);

        $this->assertSame('+212612345678', $request->phone);
    }

    // -------------------------------------------------------------------------
    // prepareForValidation — phone normalization (Update)
    // -------------------------------------------------------------------------

    public function test_update_local_phone_is_normalized_before_validation(): void
    {
        $customer = Customer::factory()->create();
        $request  = $this->makeUpdateRequest($customer);
        $request->merge(['phone' => '0612345678']);
        $this->callPrepare($request);

        $this->assertSame('+212612345678', $request->phone);
    }

    public function test_update_double_zero_phone_is_normalized_before_validation(): void
    {
        $customer = Customer::factory()->create();
        $request  = $this->makeUpdateRequest($customer);
        $request->merge(['phone' => '00212612345678']);
        $this->callPrepare($request);

        $this->assertSame('+212612345678', $request->phone);
    }

    public function test_update_already_normalized_phone_is_unchanged(): void
    {
        $customer = Customer::factory()->create();
        $request  = $this->makeUpdateRequest($customer);
        $request->merge(['phone' => '+212612345678']);
        $this->callPrepare($request);

        $this->assertSame('+212612345678', $request->phone);
    }

    // -------------------------------------------------------------------------
    // StoreCustomerRequest — validation rules
    // -------------------------------------------------------------------------

    public function test_store_valid_payload_passes(): void
    {
        $rules = (new StoreCustomerRequest())->rules();

        $v = Validator::make([
            'first_name' => 'Youssef',
            'last_name'  => 'El Amrani',
            'phone'      => '+212612345678',
            'email'      => 'youssef@example.com',
        ], $rules);

        $this->assertFalse($v->fails());
    }

    public function test_store_first_name_is_required(): void
    {
        $rules = (new StoreCustomerRequest())->rules();
        $v     = Validator::make(['last_name' => 'Test', 'phone' => '+212612345678'], $rules);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('first_name', $v->errors()->toArray());
    }

    public function test_store_last_name_is_required(): void
    {
        $rules = (new StoreCustomerRequest())->rules();
        $v     = Validator::make(['first_name' => 'Test', 'phone' => '+212612345678'], $rules);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('last_name', $v->errors()->toArray());
    }

    public function test_store_phone_must_match_moroccan_regex(): void
    {
        $rules = (new StoreCustomerRequest())->rules();

        foreach (['123456789', 'abc', '0412345678', '+33612345678'] as $invalid) {
            $v = Validator::make(['first_name' => 'A', 'last_name' => 'B', 'phone' => $invalid], $rules);
            $this->assertArrayHasKey('phone', $v->errors()->toArray(), "Expected phone '$invalid' to fail");
        }
    }

    public function test_store_phone_accepts_valid_moroccan_formats(): void
    {
        $rules = (new StoreCustomerRequest())->rules();

        foreach (['+212612345678', '+212712345678', '+212512345678'] as $valid) {
            $v      = Validator::make(['first_name' => 'A', 'last_name' => 'B', 'phone' => $valid], $rules);
            $errors = $v->errors()->toArray();
            $this->assertArrayNotHasKey('phone', $errors, "Expected phone '$valid' to pass regex");
        }
    }

    public function test_store_email_is_optional(): void
    {
        $rules = (new StoreCustomerRequest())->rules();
        $v     = Validator::make(['first_name' => 'A', 'last_name' => 'B', 'phone' => '+212612345678'], $rules);

        $this->assertFalse($v->fails());
    }

    public function test_store_email_must_be_valid_format(): void
    {
        $rules = (new StoreCustomerRequest())->rules();
        $v     = Validator::make(['first_name' => 'A', 'last_name' => 'B', 'phone' => '+212612345678', 'email' => 'not-an-email'], $rules);

        $this->assertArrayHasKey('email', $v->errors()->toArray());
    }

    public function test_store_phone_unique_is_enforced(): void
    {
        Customer::factory()->create(['phone' => '+212611111111']);

        $rules = (new StoreCustomerRequest())->rules();
        $v     = Validator::make(['first_name' => 'A', 'last_name' => 'B', 'phone' => '+212611111111'], $rules);

        $this->assertArrayHasKey('phone', $v->errors()->toArray());
    }

    public function test_store_email_unique_is_enforced(): void
    {
        Customer::factory()->create(['email' => 'taken@example.com']);

        $rules = (new StoreCustomerRequest())->rules();
        $v     = Validator::make([
            'first_name' => 'A',
            'last_name'  => 'B',
            'phone'      => '+212622222222',
            'email'      => 'taken@example.com',
        ], $rules);

        $this->assertArrayHasKey('email', $v->errors()->toArray());
    }

    // -------------------------------------------------------------------------
    // UpdateCustomerRequest — validation rules
    // -------------------------------------------------------------------------

    public function test_update_valid_payload_passes(): void
    {
        $customer = Customer::factory()->create();
        $rules    = $this->makeUpdateRequest($customer)->rules();

        $v = Validator::make([
            'first_name' => 'Youssef',
            'last_name'  => 'El Amrani',
            'phone'      => '+212612345678',
            'email'      => 'youssef@example.com',
        ], $rules);

        $this->assertFalse($v->fails());
    }

    public function test_update_phone_allows_own_existing_phone(): void
    {
        $customer = Customer::factory()->create(['phone' => '+212611111111']);
        $rules    = $this->makeUpdateRequest($customer)->rules();

        $v = Validator::make([
            'first_name' => 'A',
            'last_name'  => 'B',
            'phone'      => '+212611111111',
        ], $rules);

        $this->assertArrayNotHasKey('phone', $v->errors()->toArray());
    }

    public function test_update_phone_rejects_another_customers_phone(): void
    {
        Customer::factory()->create(['phone' => '+212611111111']);
        $customer = Customer::factory()->create(['phone' => '+212622222222']);
        $rules    = $this->makeUpdateRequest($customer)->rules();

        $v = Validator::make([
            'first_name' => 'A',
            'last_name'  => 'B',
            'phone'      => '+212611111111',
        ], $rules);

        $this->assertArrayHasKey('phone', $v->errors()->toArray());
    }

    public function test_update_email_allows_own_existing_email(): void
    {
        $customer = Customer::factory()->create(['email' => 'mine@example.com']);
        $rules    = $this->makeUpdateRequest($customer)->rules();

        $v = Validator::make([
            'first_name' => 'A',
            'last_name'  => 'B',
            'phone'      => '+212611111111',
            'email'      => 'mine@example.com',
        ], $rules);

        $this->assertArrayNotHasKey('email', $v->errors()->toArray());
    }

    public function test_update_email_rejects_another_customers_email(): void
    {
        Customer::factory()->create(['email' => 'taken@example.com']);
        $customer = Customer::factory()->create(['email' => 'mine@example.com']);
        $rules    = $this->makeUpdateRequest($customer)->rules();

        $v = Validator::make([
            'first_name' => 'A',
            'last_name'  => 'B',
            'phone'      => '+212622222222',
            'email'      => 'taken@example.com',
        ], $rules);

        $this->assertArrayHasKey('email', $v->errors()->toArray());
    }

    public function test_update_first_name_is_required(): void
    {
        $customer = Customer::factory()->create();
        $rules    = $this->makeUpdateRequest($customer)->rules();
        $v        = Validator::make(['last_name' => 'Test', 'phone' => '+212612345678'], $rules);

        $this->assertArrayHasKey('first_name', $v->errors()->toArray());
    }

    public function test_update_last_name_is_required(): void
    {
        $customer = Customer::factory()->create();
        $rules    = $this->makeUpdateRequest($customer)->rules();
        $v        = Validator::make(['first_name' => 'Test', 'phone' => '+212612345678'], $rules);

        $this->assertArrayHasKey('last_name', $v->errors()->toArray());
    }

    public function test_update_phone_must_match_moroccan_regex(): void
    {
        $customer = Customer::factory()->create();
        $rules    = $this->makeUpdateRequest($customer)->rules();

        foreach (['123456789', 'abc', '0412345678', '+33612345678'] as $invalid) {
            $v = Validator::make(['first_name' => 'A', 'last_name' => 'B', 'phone' => $invalid], $rules);
            $this->assertArrayHasKey('phone', $v->errors()->toArray(), "Expected phone '$invalid' to fail");
        }
    }

    public function test_update_email_is_optional(): void
    {
        $customer = Customer::factory()->create();
        $rules    = $this->makeUpdateRequest($customer)->rules();
        $v        = Validator::make(['first_name' => 'A', 'last_name' => 'B', 'phone' => '+212612345678'], $rules);

        $this->assertFalse($v->fails());
    }
}
