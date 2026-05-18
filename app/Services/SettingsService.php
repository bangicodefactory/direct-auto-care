<?php

namespace App\Services;

use App\Models\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SettingsService
{
    public function get(): Settings
    {
        return Settings::firstOrCreate([]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data, ?UploadedFile $logo = null): Settings
    {
        $settings = $this->get();

        if ($logo) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }
            $data['logo_path'] = $logo->store('logos', 'public');
        }

        $settings->update($data);

        return $settings;
    }
}
