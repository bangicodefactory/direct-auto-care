<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class ImportProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    /**
     * Validate only the uploaded file here; per-row business validation is done
     * in {@see \App\Services\Import\ProductImportService} so a bad row can be
     * skipped and reported rather than failing the whole upload.
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Veuillez sélectionner un fichier à importer.',
            'file.mimes'    => 'Le fichier doit être au format Excel (.xlsx).',
            'file.max'      => 'Le fichier ne doit pas dépasser 5 Mo.',
        ];
    }
}
