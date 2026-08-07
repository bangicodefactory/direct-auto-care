<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    /**
     * A client holding a facture cannot be deleted, by anyone.
     *
     * The facture carries a fiscal number issued the moment it was created, so
     * removing its client would orphan a numbered document. Devis do not count.
     */
    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasRole('admin') && ! $customer->hasFactures();
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return false;
    }
}
