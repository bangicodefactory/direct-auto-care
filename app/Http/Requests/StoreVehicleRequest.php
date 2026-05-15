<?php

namespace App\Http\Requests;

use App\Enums\FuelType;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Vehicle::class);
    }

    public function rules(): array
    {
        return [
            'customer_id'   => ['required', 'integer', 'exists:customers,id'],
            'make'          => ['required', 'string', 'max:100'],
            'model'         => ['required', 'string', 'max:100'],
            'year'          => ['required', 'integer', 'between:1950,' . (date('Y') + 1)],
            'license_plate' => ['required', 'string', 'max:20', Rule::unique('vehicles', 'license_plate')->whereNull('deleted_at')],
            'vin'           => ['nullable', 'string', 'size:17', Rule::unique('vehicles', 'vin')->whereNull('deleted_at')],
            'mileage'       => ['nullable', 'integer', 'min:0'],
            'fuel_type'     => ['required', Rule::in(array_column(FuelType::cases(), 'value'))],
            'color'         => ['nullable', 'string', 'max:50'],
        ];
    }
}
