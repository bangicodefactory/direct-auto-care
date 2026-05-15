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
            'license_plate' => ['required', 'string', 'max:20', 'unique:vehicles,license_plate'],
            'vin'           => ['nullable', 'string', 'size:17', 'unique:vehicles,vin'],
            'mileage'       => ['nullable', 'integer', 'min:0'],
            'fuel_type'     => ['required', Rule::in(array_column(FuelType::cases(), 'value'))],
            'color'         => ['nullable', 'string', 'max:50'],
        ];
    }
}
