<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],

            'phone' => [
                'required',
                'unique:customers,phone',
                'regex:/^(?:\+212|0)([5-7]\d{8})$/',
            ],

            'email' => ['nullable', 'email', 'max:150'],

            'address' => ['nullable', 'string'],
            'notes'   => ['nullable', 'string'],

            'notify_email' => ['boolean'],
            'notify_whatsapp' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Le prénom est requis.',
            'first_name.string' => 'Le prénom doit être une chaîne de caractères.',
            'first_name.max' => 'Le prénom ne doit pas dépasser 100 caractères.',

            'last_name.required' => 'Le nom est requis.',
            'last_name.string' => 'Le nom doit être une chaîne de caractères.',
            'last_name.max' => 'Le nom ne doit pas dépasser 100 caractères.',

            'phone.required' => 'Le téléphone est requis.',
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'phone.regex' => 'Le téléphone doit être au format marocain (0612345678 ou +212612345678).',

            'email.email' => 'L\'email doit être une adresse email valide.',
            'email.max' => 'L\'email ne doit pas dépasser 150 caractères.',
        ];
    }
}
