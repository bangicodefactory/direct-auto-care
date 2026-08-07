<?php

namespace App\Http\Requests;

use App\Enums\CustomerType;
use App\Models\Customer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    private ?Customer $trashedCustomer = null;

    /**
     * Whether the trashed-client lookup has run, so the unique rules, the
     * confirmation check and the controller share a single query.
     */
    private bool $trashedCustomerResolved = false;

    public function authorize(): bool
    {
        return $this->user()->can('create', Customer::class);
    }

    /**
     * The soft-deleted client holding the submitted phone number, if any.
     *
     * A trashed row keeps its phone number reserved — the unique index on
     * `customers.phone` spans trashed rows — so rather than rejecting a
     * returning customer as a duplicate, the controller restores this record
     * in place. The lookup is by phone alone: it is required, unique, and the
     * de facto identity of a client in this garage.
     */
    public function trashedCustomer(): ?Customer
    {
        if (! $this->trashedCustomerResolved) {
            $phone = $this->input('phone');

            $this->trashedCustomer = $phone
                ? Customer::onlyTrashed()->where('phone', $phone)->first()
                : null;

            $this->trashedCustomerResolved = true;
        }

        return $this->trashedCustomer;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if (! $this->filled('type')) {
            $merge['type'] = CustomerType::Individual->value;
        }

        if ($this->phone) {
            $merge['phone'] = $this->normalizePhone($this->phone);
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        // Only the client we are about to restore is exempt from the unique
        // checks. Every other row — live or trashed — still blocks, because the
        // database indexes on phone and email do not exclude trashed rows and
        // would otherwise fail the write with a duplicate-key error.
        $restorable = $this->trashedCustomer();

        return [
            'type'            => ['nullable', Rule::enum(CustomerType::class)],
            'company_name'    => ['nullable', 'required_if:type,company', 'string', 'max:191'],
            'ice'             => ['nullable', 'string', 'max:50'],
            'if'              => ['nullable', 'string', 'max:50'],
            'patente'         => ['nullable', 'string', 'max:50'],
            'ville'           => ['nullable', 'string', 'max:100'],
            'first_name'      => ['nullable', 'required_unless:type,company', 'string', 'max:100'],
            'last_name'       => ['nullable', 'required_unless:type,company', 'string', 'max:100'],
            'phone'           => ['required', 'string', 'regex:/^\+212[5-7]\d{8}$/', Rule::unique('customers', 'phone')->ignore($restorable)],
            'email'           => ['nullable', 'email', 'max:150', Rule::unique('customers', 'email')->ignore($restorable)],
            'address'         => ['nullable', 'string'],
            'notes'           => ['nullable', 'string'],
            'notify_email'    => ['boolean'],
            'notify_whatsapp' => ['boolean'],
            'confirm_restore' => ['boolean'],
        ];
    }

    /**
     * Guard the restore behind an explicit confirmation whenever the trashed
     * client carries history.
     *
     * Phone numbers get recycled, so the person at the counter is not
     * necessarily the client who owned this number before. Restoring silently
     * would hand them someone else's vehicles and invoices — and because the
     * invoice PDF reads the customer live rather than from a snapshot, it
     * would also rewrite the client block on documents already issued. A
     * record with nothing attached carries none of that risk, so it is still
     * restored without interrupting the user.
     */
    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $customer = $this->trashedCustomer();

            if (! $customer || $this->boolean('confirm_restore')) {
                return;
            }

            $history = $this->historySummary($customer);

            if (! $history) {
                return;
            }

            $validator->errors()->add('confirm_restore', sprintf(
                'Ce numéro appartient à %s, un client supprimé (%s). Confirmez pour restaurer sa fiche et tout son historique, ou saisissez un autre numéro.',
                $customer->display_name ?: 'un client sans nom',
                $history,
            ));
        });
    }

    /**
     * A French summary of what a trashed client would drag back with them,
     * e.g. "2 véhicules, 10 factures et 1 rendez-vous". Null when the record
     * has nothing attached.
     */
    private function historySummary(Customer $customer): ?string
    {
        $counts = [
            ['count' => $customer->vehicles()->count(), 'singular' => 'véhicule', 'plural' => 'véhicules'],
            ['count' => $customer->invoices()->count(), 'singular' => 'facture', 'plural' => 'factures'],
            ['count' => $customer->appointments()->count(), 'singular' => 'rendez-vous', 'plural' => 'rendez-vous'],
        ];

        $parts = [];

        foreach ($counts as $item) {
            if ($item['count'] > 0) {
                $parts[] = $item['count'] . ' ' . ($item['count'] > 1 ? $item['plural'] : $item['singular']);
            }
        }

        if ($parts === []) {
            return null;
        }

        if (count($parts) === 1) {
            return $parts[0];
        }

        $last = array_pop($parts);

        return implode(', ', $parts) . ' et ' . $last;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company_name.required_if' => 'La raison sociale est obligatoire pour une entreprise.',
            'first_name.required_unless' => 'Le prénom est obligatoire.',
            'last_name.required_unless' => 'Le nom de famille est obligatoire.',
        ];
    }

    private function normalizePhone(string $value): string
    {
        $phone = preg_replace('/[\s\-().]+/', '', $value);

        if (str_starts_with($phone, '+212')) {
            return $phone;
        }

        if (str_starts_with($phone, '00212')) {
            return '+' . substr($phone, 2);
        }

        if (str_starts_with($phone, '0')) {
            return '+212' . substr($phone, 1);
        }

        return '+212' . $phone;
    }
}
