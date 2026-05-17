<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Appointment $appointment): bool
    {
        return false;
    }
}
