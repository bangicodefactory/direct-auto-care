<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('service'));
    }

    public function rules(): array
    {
        return [
            // Unique among live rows only — a soft-deleted name may be reused.
            'name'             => ['required', 'string', 'max:150', Rule::unique('services', 'name')->whereNull('deleted_at')->ignore($this->route('service'))],
            'description'      => ['nullable', 'string', 'max:500'],
            'default_price_ht' => ['required', 'numeric', 'min:0'],
            'is_active'        => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'             => 'Le nom du service est obligatoire.',
            'name.unique'               => 'Un service portant ce nom existe déjà.',
            'default_price_ht.required' => 'Le prix par défaut HT est obligatoire.',
            'default_price_ht.numeric'  => 'Le prix par défaut HT doit être un nombre.',
            'default_price_ht.min'      => 'Le prix par défaut HT ne peut pas être négatif.',
        ];
    }
}
