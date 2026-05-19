<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\Request;
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
            ->when($request->string('search')->trim()->value(), function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy($sortField, $sortDirection)
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($c) => [
                'id'         => $c->id,
                'first_name' => $c->first_name,
                'last_name'  => $c->last_name,
                'phone'      => $c->phone,
                'email'      => $c->email,
            ]);

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters'   => (object) $request->only(['search', 'sort', 'direction']),
            'can'       => ['delete' => auth()->user()->can('delete', new Customer())],
        ]);
    }

    public function create()
    {
        $this->authorize('create', Customer::class);

        return Inertia::render('Customers/Create');
    }

    public function store(StoreCustomerRequest $request)
    {
        $customer = Customer::create($request->validated());

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Client créé avec succès.');
    }

    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);

        $customer->load(['vehicles', 'invoices', 'appointments']);

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
            'can'      => ['delete' => auth()->user()->can('delete', $customer)],
        ]);
    }

    public function edit(Customer $customer)
    {
        $this->authorize('update', $customer);

        return Inertia::render('Customers/Edit', [
            'customer' => $customer,
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
