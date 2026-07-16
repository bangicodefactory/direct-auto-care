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
        $merge = [];

        if (! $this->filled('type')) {
            $merge['type'] = CustomerType::Individual->value;
        }

        if ($this->phone) {
            $merge['phone'] = $this->normalizePhone($this->phone);
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'type'            => ['nullable', Rule::enum(CustomerType::class)],
            'company_name'    => ['nullable', 'required_if:type,company', 'string', 'max:191'],
            'ice'             => ['nullable', 'string', 'max:50'],
            'if'              => ['nullable', 'string', 'max:50'],
            'patente'         => ['nullable', 'string', 'max:50'],
            'ville'           => ['nullable', 'string', 'max:100'],
            'first_name'      => ['nullable', 'required_unless:type,company', 'string', 'max:100'],
            'last_name'       => ['nullable', 'required_unless:type,company', 'string', 'max:100'],
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
            'company_name.required_if' => 'La raison sociale est obligatoire pour une entreprise.',
            'first_name.required_unless' => 'Le prénom est obligatoire.',
            'last_name.required_unless' => 'Le nom de famille est obligatoire.',
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
