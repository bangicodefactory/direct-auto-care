<?php

namespace App\Http\Requests;

use App\Enums\CustomerType;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Customer::class);
    }

    protected function prepareForValidation(): void
    {
        if ($this->phone) {
            $this->merge(['phone' => $this->normalizePhone($this->phone)]);
        }
    }

    public function rules(): array
    {
        return [
            'first_name'      => ['required', 'string', 'max:100'],
            'last_name'       => ['required', 'string', 'max:100'],
            'type'            => ['nullable', Rule::enum(CustomerType::class)],
            'ice'             => ['nullable', 'required_if:type,company', 'string', 'regex:/^\d{15}$/'],
            'phone'           => ['required', 'string', 'regex:/^\+212[5-7]\d{8}$/', 'unique:customers,phone'],
            'email'           => ['nullable', 'email', 'max:150', 'unique:customers,email'],
            'address'         => ['nullable', 'string'],
            'notes'           => ['nullable', 'string'],
            'notify_email'    => ['boolean'],
            'notify_whatsapp' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ice.required_if' => 'L\'ICE est obligatoire pour une société.',
            'ice.regex'       => 'L\'ICE doit comporter exactement 15 chiffres.',
        ];
    }

    private function normalizePhone(string $value): string
    {
        $phone = preg_replace('/[\s\-().]+/', '', $value);

        if (str_starts_with($phone, '+212')) {
            return $phone;
        }

        if (str_starts_with($phone, '00212')) {
            return '+' . substr($phone, 2);
        }

        if (str_starts_with($phone, '0')) {
            return '+212' . substr($phone, 1);
        }

        return '+212' . $phone;
    }
}
