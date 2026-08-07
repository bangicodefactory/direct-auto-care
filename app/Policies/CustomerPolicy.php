<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CustomerPolicy
{
    /**
     * Marks a delete denial caused by the client's factures rather than by the
     * user's role, so only that case is explained back to them.
     */
    public const DENIED_HAS_FACTURES = 'customer_has_factures';

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
     *
     * Denials carry {@see self::DENIED_HAS_FACTURES} only when the factures are
     * what stands in the way, so a caller can explain that to the user without
     * misattributing a plain lack of permission to them.
     */
    public function delete(User $user, Customer $customer): Response
    {
        if (! $user->hasRole('admin')) {
            return Response::deny();
        }

        $factures = $customer->facturesCount();

        if ($factures > 0) {
            return Response::deny(
                sprintf(
                    'Ce client a %d %s : sa fiche ne peut pas être supprimée, la numérotation fiscale doit rester intacte.',
                    $factures,
                    $factures > 1 ? 'factures' : 'facture',
                ),
                self::DENIED_HAS_FACTURES,
            );
        }

        return Response::allow();
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
