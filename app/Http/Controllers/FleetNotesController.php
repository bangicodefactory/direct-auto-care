<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\VehicleNote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Notes flotte": a dated notes logbook per vehicle, limited to the vehicles
 * of customers flagged as fleet clients.
 */
class FleetNotesController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', VehicleNote::class);

        $fleetCustomers = Customer::fleetNotes()->get()
            ->map(fn (Customer $c) => ['id' => $c->id, 'display_name' => $c->display_name])
            ->sortBy('display_name')
            ->values();

        $vehicles = Vehicle::query()
            ->whereHas('customer', fn (Builder $q) => $q->fleetNotes())
            ->with(['customer', 'latestNote'])
            ->withCount('notes')
            ->withExists(['serviceOrders as in_shop' => fn (Builder $q) => $q->active()])
            ->when($request->integer('customer'), fn (Builder $q, int $customerId) => $q->where('customer_id', $customerId))
            ->when($request->string('search')->trim()->value(), function (Builder $q, string $search) {
                $q->where(function (Builder $q) use ($search) {
                    $q->where('license_plate', 'LIKE', "%{$search}%")
                        ->orWhere('make', 'LIKE', "%{$search}%")
                        ->orWhere('model', 'LIKE', "%{$search}%");
                });
            })
            ->orderByDesc('in_shop')
            ->orderBy('license_plate')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Vehicle $v) => [
                'id'            => $v->id,
                'license_plate' => $v->license_plate,
                'make'          => $v->make,
                'model'         => $v->model,
                'year'          => $v->year,
                'customer'      => $v->customer->display_name,
                'in_shop'       => (bool) $v->in_shop,
                'notes_count'   => $v->notes_count,
                'latest_note'   => $v->latestNote ? [
                    'excerpt'    => Str::limit($v->latestNote->body, 120),
                    'created_at' => $v->latestNote->created_at->format('d/m/Y H:i'),
                ] : null,
            ]);

        return Inertia::render('FleetNotes/Index', [
            'vehicles'  => $vehicles,
            'customers' => $fleetCustomers,
            'filters'   => (object) $request->only(['search', 'customer']),
        ]);
    }

    public function show(Request $request, Vehicle $vehicle): Response
    {
        $this->authorize('viewAny', VehicleNote::class);

        // A vehicle outside the fleet is not part of this section at all.
        abort_unless((bool) $vehicle->customer?->fleet_notes_enabled, 404);

        $vehicle->load(['notes.user']);
        $openOrder = $vehicle->serviceOrders()->active()->latest()->first();
        $user = $request->user();

        return Inertia::render('FleetNotes/Show', [
            'vehicle' => [
                'id'            => $vehicle->id,
                'license_plate' => $vehicle->license_plate,
                'make'          => $vehicle->make,
                'model'         => $vehicle->model,
                'year'          => $vehicle->year,
                'customer'      => [
                    'id'           => $vehicle->customer->id,
                    'display_name' => $vehicle->customer->display_name,
                ],
                'open_order'    => $openOrder ? [
                    'id'     => $openOrder->id,
                    'number' => $openOrder->number,
                    'status' => $openOrder->status->label(),
                ] : null,
            ],
            'notes' => $vehicle->notes->map(fn (VehicleNote $n) => [
                'id'         => $n->id,
                'body'       => $n->body,
                'author'     => $n->user?->name ?? 'Utilisateur supprimé',
                'created_at' => $n->created_at->format('d/m/Y H:i'),
                'edited'     => $n->updated_at->greaterThan($n->created_at),
                'can'        => [
                    'update' => $user->can('update', $n),
                    'delete' => $user->can('delete', $n),
                ],
            ]),
            'can' => [
                'create' => $user->can('create', [VehicleNote::class, $vehicle]),
            ],
        ]);
    }
}
