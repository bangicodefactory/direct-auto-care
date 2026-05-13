<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreCustomerRequest;
use Tests\TestCase;

class CustomerRequestTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // prepareForValidation — phone normalization
    // -------------------------------------------------------------------------

    private function callPrepare(StoreCustomerRequest $request): void
    {
        $method = new \ReflectionMethod($request, 'prepareForValidation');
        $method->invoke($request);
    }

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
        $v = Validator::make(['last_name' => 'Test', 'phone' => '+212612345678'], $rules);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('first_name', $v->errors()->toArray());
    }

    public function test_store_last_name_is_required(): void
    {
        $rules = (new StoreCustomerRequest())->rules();
        $v = Validator::make(['first_name' => 'Test', 'phone' => '+212612345678'], $rules);

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
            $v = Validator::make(['first_name' => 'A', 'last_name' => 'B', 'phone' => $valid], $rules);
            $errors = $v->errors()->toArray();
            $this->assertArrayNotHasKey('phone', $errors, "Expected phone '$valid' to pass regex");
        }
    }

    public function test_store_email_is_optional(): void
    {
        $rules = (new StoreCustomerRequest())->rules();
        $v = Validator::make(['first_name' => 'A', 'last_name' => 'B', 'phone' => '+212612345678'], $rules);

        $this->assertFalse($v->fails());
    }

    public function test_store_email_must_be_valid_format(): void
    {
        $rules = (new StoreCustomerRequest())->rules();
        $v = Validator::make(['first_name' => 'A', 'last_name' => 'B', 'phone' => '+212612345678', 'email' => 'not-an-email'], $rules);

        $this->assertArrayHasKey('email', $v->errors()->toArray());
    }

    public function test_store_phone_unique_is_enforced(): void
    {
        Customer::factory()->create(['phone' => '+212611111111']);

        $rules = (new StoreCustomerRequest())->rules();
        $v = Validator::make(['first_name' => 'A', 'last_name' => 'B', 'phone' => '+212611111111'], $rules);

        $this->assertArrayHasKey('phone', $v->errors()->toArray());
    }

    public function test_store_email_unique_is_enforced(): void
    {
        Customer::factory()->create(['email' => 'taken@example.com']);

        $rules = (new StoreCustomerRequest())->rules();
        $v = Validator::make([
            'first_name' => 'A',
            'last_name'  => 'B',
            'phone'      => '+212622222222',
            'email'      => 'taken@example.com',
        ], $rules);

        $this->assertArrayHasKey('email', $v->errors()->toArray());
    }
}
