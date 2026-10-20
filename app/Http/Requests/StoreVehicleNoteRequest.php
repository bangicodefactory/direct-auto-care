<?php

namespace App\Http\Requests;

use App\Models\VehicleNote;
use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [VehicleNote::class, $this->route('vehicle')]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'La note ne peut pas être vide.',
            'body.max'      => 'La note ne peut pas dépasser 5000 caractères.',
        ];
    }
}
