<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Settings extends Model
{
    protected $fillable = [
        'company_name',
        'company_address',
        'company_ice',
        'company_if',
        'company_rc',
        'tva_rate',
        'logo_path',
        'color_primary',
        'color_accent',
        'color_bg',
        'color_text_primary',
        'color_text_muted',
        'color_success',
        'color_warning',
        'color_error',
    ];

    protected function casts(): array
    {
        return [
            'tva_rate' => 'float',
        ];
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::url($this->logo_path) : null;
    }
}
