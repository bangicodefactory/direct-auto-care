<?php

namespace App\Policies;

use App\Models\ServiceRecord;
use App\Models\User;

class ServiceRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    public function view(User $user, ServiceRecord $serviceRecord): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    public function update(User $user, ServiceRecord $serviceRecord): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    public function delete(User $user, ServiceRecord $serviceRecord): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, ServiceRecord $serviceRecord): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, ServiceRecord $serviceRecord): bool
    {
        return false;
    }
}
