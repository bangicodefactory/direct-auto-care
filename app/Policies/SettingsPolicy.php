<?php

namespace App\Policies;

use App\Models\Settings;
use App\Models\User;

class SettingsPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Settings $settings): bool
    {
        return $user->hasRole('admin');
    }
}
