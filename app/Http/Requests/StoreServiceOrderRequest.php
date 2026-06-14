<?php

namespace App\Http\Requests;

use App\Models\ServiceOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ServiceOrder::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id'           => ['required', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'vehicle_id'            => ['required', 'integer', Rule::exists('vehicles', 'id')->where('customer_id', $this->integer('customer_id'))->whereNull('deleted_at')],
            'appointment_id'        => ['nullable', 'integer', Rule::exists('appointments', 'id')->where('customer_id', $this->integer('customer_id'))],
            'mileage_in'            => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'internal_notes'        => ['nullable', 'string', 'max:2000'],
            'reception_condition'   => ['nullable', 'array', $this->conditionKeysAreValid()],
            'reception_condition.*' => ['string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    /**
     * Body-part ids are SVG group ids (e.g. FRONT_BONNET) — constrain the map keys
     * so arbitrary data can't be persisted.
     */
    private function conditionKeysAreValid(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_array($value)) {
                return;
            }

            foreach (array_keys($value) as $key) {
                if (! is_string($key) || ! preg_match('/^[A-Z0-9_]+$/', $key)) {
                    $fail('Le schéma de l\'état du véhicule est invalide.');

                    return;
                }
            }
        };
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vehicle_id.exists' => 'Le véhicule sélectionné doit appartenir au client.',
        ];
    }
}
