<?php

namespace App\Services;

use App\Models\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SettingsService
{
    public function get(): Settings
    {
        return Cache::rememberForever('settings', function () {
            $settings = Settings::firstOrCreate(['singleton' => 1]);

            if ($settings->wasRecentlyCreated) {
                $settings->refresh();
            }

            return $settings;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data, ?UploadedFile $logo = null): Settings
    {
        $settings = Settings::firstOrCreate(['singleton' => 1]);

        if ($logo) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }
            $data['logo_path'] = $logo->store('logos', 'public');
        }

        $settings->update($data);
        Cache::forget('settings');

        return $settings;
    }
}
