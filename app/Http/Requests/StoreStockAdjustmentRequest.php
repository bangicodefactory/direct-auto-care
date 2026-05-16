<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // policy checked via controller middleware
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'not_in:0'],
            'reason'   => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.not_in' => 'La quantité doit être différente de zéro.',
        ];
    }
}
