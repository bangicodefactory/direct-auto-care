<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VehiclesController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Vehicle::class);

        $vehicles = Vehicle::query()
            ->with('customer')
            ->when($request->string('search')->trim()->value(), function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('license_plate', 'LIKE', "%{$search}%")
                        ->orWhereHas('customer', function ($q) use ($search) {
                            $q->where('first_name', 'LIKE', "%{$search}%")
                                ->orWhere('last_name', 'LIKE', "%{$search}%");
                        });
                });
            })
            ->orderBy('license_plate')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($v) => [
                'id'            => $v->id,
                'make'          => $v->make,
                'model'         => $v->model,
                'year'          => $v->year,
                'license_plate' => $v->license_plate,
                'fuel_type'     => $v->fuel_type->value,
                'customer'      => [
                    'id'   => $v->customer->id,
                    'name' => $v->customer->first_name . ' ' . $v->customer->last_name,
                ],
            ]);

        return Inertia::render('Vehicles/Index', [
            'vehicles' => $vehicles,
            'filters'  => $request->only(['search']),
            'can'      => ['delete' => auth()->user()->can('delete', new Vehicle())],
        ]);
    }

    public function create(Customer $customer)
    {
        $this->authorize('create', Vehicle::class);

        return Inertia::render('Vehicles/Create', [
            'customer' => $customer->only('id', 'first_name', 'last_name'),
        ]);
    }

    public function store(StoreVehicleRequest $request, Customer $customer)
    {
        $vehicle = Vehicle::create(
            array_merge($request->validated(), ['customer_id' => $customer->id])
        );

        return redirect()->route('vehicles.show', $vehicle)
            ->with('success', 'Véhicule créé avec succès.');
    }

    public function show(Vehicle $vehicle)
    {
        $this->authorize('view', $vehicle);

        $vehicle->load('customer');

        return Inertia::render('Vehicles/Show', [
            'vehicle' => $this->vehiclePayload($vehicle),
            'can'     => ['delete' => auth()->user()->can('delete', $vehicle)],
        ]);
    }

    public function edit(Vehicle $vehicle)
    {
        $this->authorize('update', $vehicle);

        $vehicle->load('customer');

        return Inertia::render('Vehicles/Edit', [
            'vehicle' => $this->vehiclePayload($vehicle),
        ]);
    }

    private function vehiclePayload(Vehicle $vehicle): array
    {
        return [
            'id'            => $vehicle->id,
            'make'          => $vehicle->make,
            'model'         => $vehicle->model,
            'year'          => $vehicle->year,
            'license_plate' => $vehicle->license_plate,
            'vin'           => $vehicle->vin,
            'mileage'       => $vehicle->mileage,
            'fuel_type'     => $vehicle->fuel_type->value,
            'color'         => $vehicle->color,
            'customer'      => [
                'id'         => $vehicle->customer->id,
                'first_name' => $vehicle->customer->first_name,
                'last_name'  => $vehicle->customer->last_name,
            ],
        ];
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle)
    {
        try {
            $vehicle->update($request->validated());
        } catch (\DomainException $e) {
            return back()->withErrors(['mileage' => $e->getMessage()]);
        }

        return redirect()->route('vehicles.show', $vehicle)
            ->with('success', 'Véhicule mis à jour avec succès.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $this->authorize('delete', $vehicle);

        $vehicle->delete();

        return redirect()->route('vehicles.index')
            ->with('success', 'Véhicule supprimé.');
    }
}
