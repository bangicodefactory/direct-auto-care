<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // handled by ServiceRecordsController::middleware()
    }

    public function rules(): array
    {
        return [
            'service_type_ids'   => ['required', 'array', 'min:1'],
            'service_type_ids.*' => ['integer', 'exists:service_types,id'],
            'mileage_at_service' => ['nullable', 'integer', 'min:0'],
            'notes'              => ['nullable', 'string', 'max:2000'],
            'serviced_at'        => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
