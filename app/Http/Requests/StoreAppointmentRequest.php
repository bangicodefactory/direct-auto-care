<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Appointment::class);
    }

    public function rules(): array
    {
        return [
            'customer_id'        => ['required', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'vehicle_id'         => ['nullable', 'integer', Rule::exists('vehicles', 'id')->where('customer_id', $this->integer('customer_id'))->whereNull('deleted_at')],
            'scheduled_at'       => ['required', 'date'],
            'duration'           => ['required', 'integer', 'min:15', 'max:480'],
            'service_type_ids'   => ['nullable', 'array'],
            'service_type_ids.*' => ['integer', 'exists:service_types,id'],
            'notes'              => ['nullable', 'string', 'max:2000'],
        ];
    }
}
