<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Customer;
use App\Models\ServiceRecord;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehiclesController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Vehicle::class);

        $allowedSorts = ['license_plate', 'make', 'year'];
        $sort      = in_array($request->input('sort'), $allowedSorts) ? $request->input('sort') : 'license_plate';
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

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
            ->orderBy($sort, $direction)
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
            'filters'  => (object) $request->only(['search', 'sort', 'direction']),
            'can'      => ['delete' => auth()->user()->can('delete', new Vehicle())],
        ]);
    }

    public function standaloneCreate(): Response
    {
        $this->authorize('create', Vehicle::class);

        // Loads the full customer list for the combobox. Acceptable for a small garage;
        // revisit with server-side search if the list grows large.
        return Inertia::render('Vehicles/Create', [
            'customers' => Customer::select('id', 'first_name', 'last_name', 'phone')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
        ]);
    }

    public function standaloneStore(StoreVehicleRequest $request): RedirectResponse
    {
        $vehicle = Vehicle::create($request->validated());

        return redirect()->route('vehicles.show', $vehicle)
            ->with('success', 'Véhicule créé avec succès.');
    }

    public function create(Customer $customer): Response
    {
        $this->authorize('create', Vehicle::class);

        return Inertia::render('Vehicles/Create', [
            'customer' => $customer->only('id', 'first_name', 'last_name'),
        ]);
    }

    public function store(StoreVehicleRequest $request, Customer $customer): RedirectResponse
    {
        $vehicle = Vehicle::create(
            array_merge($request->validated(), ['customer_id' => $customer->id])
        );

        return redirect()->route('vehicles.show', $vehicle)
            ->with('success', 'Véhicule créé avec succès.');
    }

    public function show(Vehicle $vehicle): Response
    {
        $this->authorize('view', $vehicle);

        // All records loaded; acceptable for a small garage — revisit with pagination if lists grow large.
        $vehicle->load([
            'customer',
            'serviceRecords' => fn ($q) => $q->with('serviceTypes')->latest('serviced_at'),
            'appointments'   => fn ($q) => $q->latest(),
        ]);

        $user = auth()->user();

        return Inertia::render('Vehicles/Show', [
            'vehicle' => array_merge(
                $this->vehiclePayload($vehicle),
                [
                    'service_records' => $vehicle->serviceRecords->map(fn ($r) => [
                        'id'                 => $r->id,
                        'serviced_at'        => $r->serviced_at->translatedFormat('d/m/Y'),
                        'mileage_at_service' => $r->mileage_at_service,
                        'notes'              => $r->notes,
                        'service_types'      => $r->serviceTypes->pluck('name')->values()->toArray(),
                        'can_edit'           => $user->can('update', $r),
                        'can_delete'         => $user->can('delete', $r),
                    ]),
                    'appointments' => $vehicle->appointments->map(fn ($a) => [
                        'id'         => $a->id,
                        'created_at' => $a->created_at->translatedFormat('d/m/Y'),
                    ]),
                ]
            ),
            'can' => [
                'delete'                => $user->can('delete', $vehicle),
                'create_service_record' => $user->can('create', ServiceRecord::class),
            ],
        ]);
    }

    public function edit(Vehicle $vehicle): Response
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

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        try {
            $vehicle->update($request->validated());
        } catch (\DomainException $e) {
            return back()->withErrors(['mileage' => $e->getMessage()]);
        }

        return redirect()->route('vehicles.show', $vehicle)
            ->with('success', 'Véhicule mis à jour avec succès.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('delete', $vehicle);

        $vehicle->delete();

        return redirect()->route('vehicles.index')
            ->with('success', 'Véhicule supprimé.');
    }
}
