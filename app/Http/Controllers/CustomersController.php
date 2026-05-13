<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Inertia\Inertia;

class CustomersController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Customer::class);

        return Inertia::render('Customers/Index', [
            'customers' => Customer::orderBy('last_name')->orderBy('first_name')
                ->paginate(15)
                ->through(fn ($c) => [
                    'id'         => $c->id,
                    'first_name' => $c->first_name,
                    'last_name'  => $c->last_name,
                    'phone'      => $c->phone,
                    'email'      => $c->email,
                ]),
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

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
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
