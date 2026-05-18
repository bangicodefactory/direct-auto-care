<?php

namespace App\Policies;

use App\Enums\ReminderStatus;
use App\Models\Reminder;
use App\Models\User;

class ReminderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Reminder $reminder): bool
    {
        return $user->hasRole('admin');
    }

    public function trigger(User $user, Reminder $reminder): bool
    {
        return $user->hasRole('admin')
            && in_array($reminder->status, [ReminderStatus::Pending, ReminderStatus::Cancelled]);
    }

    public function cancel(User $user, Reminder $reminder): bool
    {
        return $user->hasRole('admin')
            && $reminder->status === ReminderStatus::Pending;
    }
}
