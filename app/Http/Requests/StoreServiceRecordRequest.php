<?php

namespace App\Http\Requests;

use App\Models\ServiceRecord;
use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ServiceRecord::class);
    }

    public function rules(): array
    {
        $vehicle = $this->route('vehicle');

        return [
            'service_type_ids'   => ['required', 'array', 'min:1'],
            'service_type_ids.*' => ['integer', 'exists:service_types,id'],
            'mileage_at_service' => ['nullable', 'integer', 'min:' . ($vehicle->mileage ?? 0)],
            'notes'              => ['nullable', 'string', 'max:2000'],
            'serviced_at'        => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
