<?php

namespace App\Policies;

use App\Models\ServiceOrder;
use App\Models\User;

class ServiceOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    public function view(User $user, ServiceOrder $serviceOrder): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    public function update(User $user, ServiceOrder $serviceOrder): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    public function delete(User $user, ServiceOrder $serviceOrder): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, ServiceOrder $serviceOrder): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, ServiceOrder $serviceOrder): bool
    {
        return $user->hasRole('admin');
    }
}
