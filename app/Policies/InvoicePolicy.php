<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasAnyRole(['admin', 'employee'])
            && $invoice->status === InvoiceStatus::Draft;
    }

    public function convert(User $user, Invoice $invoice): bool
    {
        return $user->hasAnyRole(['admin', 'employee'])
            && $invoice->type === InvoiceType::Quote
            && ! $invoice->convertedToInvoice()->exists();
    }

    public function issue(User $user, Invoice $invoice): bool
    {
        return $user->hasAnyRole(['admin', 'employee'])
            && $invoice->type === InvoiceType::Invoice
            && $invoice->status === InvoiceStatus::Draft;
    }

    public function markPaid(User $user, Invoice $invoice): bool
    {
        return $user->hasAnyRole(['admin', 'employee'])
            && $invoice->status === InvoiceStatus::Issued;
    }

    public function cancel(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('admin')
            && in_array($invoice->status, [InvoiceStatus::Draft, InvoiceStatus::Issued]);
    }

    /**
     * Only drafts and cancelled documents may be deleted: an issued or paid
     * facture must be cancelled first (restoring stock and keeping the audit
     * trail), then deleted if truly unwanted.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('admin')
            && in_array($invoice->status, [InvoiceStatus::Draft, InvoiceStatus::Cancelled]);
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return false;
    }
}
