<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('serviceOrder'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id'    => ['nullable', 'integer', 'exists:products,id'],
            // A free-text line (no product) must carry its own description; for a
            // product line the description defaults to the product name in the service.
            'description'   => ['required_without:product_id', 'nullable', 'string', 'max:500'],
            'quantity'      => ['required', 'integer', 'min:1'],
            'unit_price_ht' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.exists'            => "Le produit sélectionné n'existe pas.",
            'description.required_without' => 'La description est obligatoire pour une ligne sans produit.',
            'description.max'              => 'La description ne peut pas dépasser 500 caractères.',
            'quantity.required'           => 'La quantité est obligatoire.',
            'quantity.integer'            => 'La quantité doit être un nombre entier.',
            'quantity.min'                => 'La quantité doit être au moins 1.',
            'unit_price_ht.required'      => 'Le prix unitaire HT est obligatoire.',
            'unit_price_ht.numeric'       => 'Le prix unitaire HT doit être un nombre.',
            'unit_price_ht.min'           => 'Le prix unitaire HT ne peut pas être négatif.',
        ];
    }
}
