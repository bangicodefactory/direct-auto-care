<?php

namespace App\Http\Requests;

use App\Enums\AppointmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('appointment'));
    }

    public function rules(): array
    {
        $customerId = $this->integer('customer_id')
            ?: $this->route('appointment')->customer_id;

        return [
            'customer_id'        => ['required', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'vehicle_id'         => ['nullable', 'integer', Rule::exists('vehicles', 'id')->where('customer_id', $customerId)->whereNull('deleted_at')],
            'scheduled_at'       => ['required', 'date'],
            'duration'           => ['required', 'integer', 'min:15', 'max:480'],
            'service_type_ids'   => ['nullable', 'array'],
            'service_type_ids.*' => ['integer', 'exists:service_types,id'],
            'status'             => ['nullable', Rule::enum(AppointmentStatus::class)],
            'notes'              => ['nullable', 'string', 'max:2000'],
        ];
    }
}
