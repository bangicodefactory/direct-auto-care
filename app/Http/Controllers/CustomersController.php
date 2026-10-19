<?php

namespace App\Http\Controllers;

use App\Enums\CustomerType;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Policies\CustomerPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CustomersController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Customer::class);

        $sortField = in_array($request->get('sort'), ['last_name', 'first_name', 'email'])
            ? $request->get('sort')
            : 'last_name';

        $sortDirection = $request->get('direction') === 'desc' ? 'desc' : 'asc';

        $customers = Customer::query()
            // Counted up front so the per-row delete check below costs no query
            // of its own.
            ->withFactureCount()
            ->when($request->string('search')->trim()->value(), function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('company_name', 'LIKE', "%{$search}%")
                        ->orWhere('ice', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy($sortField, $sortDirection)
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($c) => [
                'id'           => $c->id,
                'type'         => $c->type,
                'display_name' => $c->display_name,
                'company_name' => $c->company_name,
                'first_name'   => $c->first_name,
                'last_name'    => $c->last_name,
                'phone'        => $c->phone,
                'email'        => $c->email,
                // Per row: whether a client can be deleted depends on the
                // client, not only on the role, so a blanket flag would offer
                // Supprimer on rows the policy then refuses.
                'can_delete'   => $request->user()->can('delete', $c),
            ]);

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters'   => (object) $request->only(['search', 'sort', 'direction']),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Customer::class);

        return Inertia::render('Customers/Create');
    }

    public function store(StoreCustomerRequest $request)
    {
        // A returning client whose record was soft-deleted still owns their
        // phone number, so re-adding them revives the existing row instead of
        // being rejected as a duplicate. StoreCustomerRequest has already made
        // the user confirm if this client carries history. fill() before
        // restore() keeps it to a single save; their vehicles, invoices and
        // appointments were never deleted and become visible again with them.
        if ($existing = $request->trashedCustomer()) {
            $existing->fill($this->restoredAttributes($request))->restore();

            return redirect()->route('customers.show', $existing)
                ->with('success', 'Client restauré : ce client avait été supprimé, son historique est de nouveau visible.');
        }

        $customer = Customer::create($request->validated());

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Client créé avec succès.');
    }

    /**
     * The attribute set a restored client is rebuilt from.
     *
     * A restore starts from a clean slate rather than patching the deleted
     * row: every column the form owns is reset first, so a returning client
     * never silently inherits leftovers the payload happens to omit — stale
     * fiscal identifiers on an invoice, or notification preferences the user
     * never agreed to. Only what was just submitted survives.
     *
     * @return array<string, mixed>
     */
    private function restoredAttributes(StoreCustomerRequest $request): array
    {
        $blank = array_fill_keys((new Customer())->getFillable(), null);

        $defaults = [
            'type'            => CustomerType::Individual->value,
            'notify_email'    => false,
            'notify_whatsapp' => false,
            'fleet_notes_enabled' => false,
        ];

        return array_merge($blank, $defaults, Arr::except($request->validated(), ['confirm_restore']));
    }

    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);

        $customer->load(['vehicles', 'invoices', 'appointments']);

        // One count answers both halves of the question below, so the policy
        // does not go back to the database for it.
        $customer->loadFactureCount();

        // Soft-deleted factures pin the client too, so the policy's explanation
        // covers factures the Factures tab never shows — without it, a missing
        // Supprimer button would look like a bug.
        $decision = Gate::inspect('delete', $customer);

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
            'can'      => [
                'delete'                => $decision->allowed(),
                'delete_blocked_reason' => $decision->code() === CustomerPolicy::DENIED_HAS_FACTURES
                    ? $decision->message()
                    : null,
            ],
        ]);
    }

    public function edit(Request $request, Customer $customer)
    {
        $this->authorize('update', $customer);

        return Inertia::render('Customers/Edit', [
            'customer' => $customer,
            'can'      => [
                'manage_fleet_notes' => $request->user()->can('manageFleetNotes', Customer::class),
            ],
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $customer->update($request->validated());

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Client mis à jour avec succès.');
    }

    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Client supprimé.');
    }
}
