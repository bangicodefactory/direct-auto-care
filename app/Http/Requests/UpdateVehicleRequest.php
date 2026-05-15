<?php

namespace App\Http\Requests;

use App\Enums\FuelType;
use App\Rules\MileageNotDecreasing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('vehicle'));
    }

    public function rules(): array
    {
        $vehicle = $this->route('vehicle');

        return [
            'make'          => ['required', 'string', 'max:100'],
            'model'         => ['required', 'string', 'max:100'],
            'year'          => ['required', 'integer', 'between:1950,' . (date('Y') + 1)],
            'license_plate' => ['required', 'string', 'max:20', Rule::unique('vehicles', 'license_plate')->ignore($vehicle)->whereNull('deleted_at')],
            'vin'           => ['nullable', 'string', 'size:17', Rule::unique('vehicles', 'vin')->ignore($vehicle)->whereNull('deleted_at')],
            'mileage'       => ['required', 'bail', 'integer', 'min:0', new MileageNotDecreasing($vehicle->mileage)],
            'fuel_type'     => ['required', Rule::in(array_column(FuelType::cases(), 'value'))],
            'color'         => ['nullable', 'string', 'max:50'],
        ];
    }
}
