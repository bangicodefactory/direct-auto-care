<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            // No is_active filter: matching the invoice requests — a stale form
            // referencing a since-deactivated service must still validate.
            'service_id'    => [
                'nullable',
                'integer',
                Rule::exists('services', 'id')->whereNull('deleted_at'),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value !== null && $this->input('product_id') !== null) {
                        $fail('Une ligne ne peut pas référencer à la fois un produit et un service.');
                    }
                },
            ],
            // A free-text line (no product/service) must carry its own description;
            // for a product or service line it defaults to the catalog name.
            'description'   => ['required_without_all:product_id,service_id', 'nullable', 'string', 'max:191'],
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
            'product_id.exists'                => "Le produit sélectionné n'existe pas.",
            'service_id.exists'                => "Le service sélectionné n'existe pas.",
            'description.required_without_all' => 'La description est obligatoire pour une ligne libre.',
            'description.max'                  => 'La description ne peut pas dépasser 191 caractères.',
            'quantity.required'                => 'La quantité est obligatoire.',
            'quantity.integer'                 => 'La quantité doit être un nombre entier.',
            'quantity.min'                     => 'La quantité doit être au moins 1.',
            'unit_price_ht.required'           => 'Le prix unitaire HT est obligatoire.',
            'unit_price_ht.numeric'            => 'Le prix unitaire HT doit être un nombre.',
            'unit_price_ht.min'                => 'Le prix unitaire HT ne peut pas être négatif.',
        ];
    }
}
