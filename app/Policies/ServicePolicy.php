<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    public function view(User $user, Service $service): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Service $service): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Service $service): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Service $service): bool
    {
        return false;
    }
}
