<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', \App\Models\Settings::class);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $hexColor = ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'];

        return [
            'company_name'    => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:1000'],
            'company_ice'     => ['nullable', 'string', 'max:20'],
            'company_if'      => ['nullable', 'string', 'max:20'],
            'company_rc'      => ['nullable', 'string', 'max:50'],
            'tva_rate'        => ['required', 'numeric', 'between:0,100'],
            'logo'            => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg,webp', 'max:2048'],
            'color_primary'      => $hexColor,
            'color_accent'       => $hexColor,
            'color_bg'           => $hexColor,
            'color_text_primary' => $hexColor,
            'color_text_muted'   => $hexColor,
            'color_success'      => $hexColor,
            'color_warning'      => $hexColor,
            'color_error'        => $hexColor,
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'tva_rate.required'  => 'Le taux de TVA est obligatoire.',
            'tva_rate.numeric'   => 'Le taux de TVA doit être un nombre.',
            'tva_rate.between'   => 'Le taux de TVA doit être entre 0 et 100.',
            'logo.mimes'         => 'Le logo doit être un fichier JPG, PNG, SVG ou WebP.',
            'logo.max'           => 'Le logo ne doit pas dépasser 2 Mo.',
            'regex'              => 'La couleur doit être au format hexadécimal (#RRGGBB).',
        ];
    }
}
