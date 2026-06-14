<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('serviceOrder'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mileage_in'      => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'diagnosis_notes' => ['nullable', 'string', 'max:2000'],
            'internal_notes'  => ['nullable', 'string', 'max:2000'],
        ];
    }
}
