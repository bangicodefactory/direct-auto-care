<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentOverlapException;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\ServiceType;
use App\Models\Vehicle;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:create,' . Appointment::class, only: ['create', 'store']),
            new Middleware('can:update,appointment', only: ['edit', 'update']),
            new Middleware('can:cancel,appointment', only: ['cancel']),
            new Middleware('can:delete,appointment', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Appointment::class);

        $start = rescue(
            fn () => Carbon::parse($request->string('start')->value())->startOfDay(),
            fn () => now()->startOfMonth()->startOfDay(),
            false,
        );
        $end = rescue(
            fn () => Carbon::parse($request->string('end')->value())->endOfDay(),
            fn () => now()->endOfMonth()->endOfDay(),
            false,
        );

        $events = Appointment::query()
            ->with('customer:id,first_name,last_name', 'vehicle:id,license_plate,make,model', 'serviceTypes:id,name')
            ->when(
                AppointmentStatus::tryFrom($request->string('status')->value()),
                fn ($q, $status) => $q->where('status', $status->value)
            )
            ->where('scheduled_at', '>=', $start)
            ->where('scheduled_at', '<=', $end)
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn ($apt) => [
                'id'               => $apt->id,
                'title'            => $apt->customer->first_name . ' ' . $apt->customer->last_name
                    . ($apt->vehicle ? ' — ' . $apt->vehicle->license_plate : ''),
                'start'            => $apt->scheduled_at->toIso8601String(),
                'end'              => $apt->scheduled_at->copy()->addMinutes($apt->duration)->toIso8601String(),
                'duration'         => $apt->duration,
                'status'           => $apt->status->value,
                'status_label'     => $apt->status->label(),
                'notes'            => $apt->notes,
                'customer'         => [
                    'id'   => $apt->customer->id,
                    'name' => $apt->customer->first_name . ' ' . $apt->customer->last_name,
                ],
                'vehicle_id'       => $apt->vehicle_id,
                'vehicle'          => $apt->vehicle ? [
                    'id'    => $apt->vehicle->id,
                    'plate' => $apt->vehicle->license_plate,
                ] : null,
                'service_type_ids' => $apt->serviceTypes->pluck('id')->values()->all(),
            ]);

        return Inertia::render('Appointments/Index', [
            'events'       => $events,
            'filters'      => $request->only(['status', 'start', 'end']),
            'statuses'     => collect(AppointmentStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'customers'    => Inertia::defer(fn () => Customer::orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name']), 'modal'),
            'vehicles'     => Inertia::defer(fn () => Vehicle::orderBy('license_plate')->get(['id', 'customer_id', 'license_plate', 'make', 'model']), 'modal'),
            'serviceTypes' => Inertia::defer(fn () => ServiceType::orderBy('name')->get(['id', 'name']), 'modal'),
            'can'          => [
                'create' => $request->user()->can('create', Appointment::class),
                'delete' => $request->user()->hasRole('admin'),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Appointment::class);

        return Inertia::render('Appointments/Create', [
            'customers'    => Customer::orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'vehicles'     => Vehicle::orderBy('license_plate')->get(['id', 'customer_id', 'license_plate', 'make', 'model']),
            'serviceTypes' => ServiceType::orderBy('name')->get(['id', 'name']),
            'statuses'     => collect(AppointmentStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    public function store(StoreAppointmentRequest $request, AppointmentService $service): RedirectResponse
    {
        try {
            $appointment = $service->create($request->validated(), $request->user());
        } catch (AppointmentOverlapException $e) {
            return back()->withErrors(['scheduled_at' => $e->getMessage()]);
        }

        return redirect()->route('appointments.show', $appointment)
            ->with('success', 'Rendez-vous confirmé.');
    }

    public function show(Request $request, Appointment $appointment): Response
    {
        $this->authorize('view', $appointment);

        $appointment->load(['customer', 'vehicle', 'serviceTypes', 'createdBy']);

        return Inertia::render('Appointments/Show', [
            'appointment' => [
                'id'           => $appointment->id,
                'scheduled_at' => $appointment->scheduled_at->format('d/m/Y H:i'),
                'duration'     => $appointment->duration,
                'ends_at'      => $appointment->ends_at?->format('d/m/Y H:i'),
                'status'       => $appointment->status->value,
                'status_label' => $appointment->status->label(),
                'notes'        => $appointment->notes,
                'customer'     => [
                    'id'    => $appointment->customer->id,
                    'name'  => $appointment->customer->first_name . ' ' . $appointment->customer->last_name,
                    'phone' => $appointment->customer->phone,
                    'email' => $appointment->customer->email,
                ],
                'vehicle'      => $appointment->vehicle ? [
                    'id'    => $appointment->vehicle->id,
                    'plate' => $appointment->vehicle->license_plate,
                    'make'  => $appointment->vehicle->make,
                    'model' => $appointment->vehicle->model,
                ] : null,
                'services'     => $appointment->expectedServices,
                'created_by'   => $appointment->createdBy?->name,
            ],
            'can' => [
                'edit'   => $request->user()->can('update', $appointment),
                'cancel' => $request->user()->can('cancel', $appointment),
                'delete' => $request->user()->can('delete', $appointment),
            ],
        ]);
    }

    public function edit(Appointment $appointment): Response
    {
        $this->authorize('update', $appointment);
        $appointment->load('serviceTypes');

        return Inertia::render('Appointments/Edit', [
            'appointment'  => [
                'id'               => $appointment->id,
                'customer_id'      => $appointment->customer_id,
                'vehicle_id'       => $appointment->vehicle_id,
                'scheduled_at'     => $appointment->scheduled_at->format('Y-m-d\TH:i'),
                'duration'         => $appointment->duration,
                'status'           => $appointment->status->value,
                'notes'            => $appointment->notes,
                'service_type_ids' => $appointment->serviceTypes->pluck('id')->all(),
            ],
            'customers'    => Customer::orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'vehicles'     => Vehicle::orderBy('license_plate')->get(['id', 'customer_id', 'license_plate', 'make', 'model']),
            'serviceTypes' => ServiceType::orderBy('name')->get(['id', 'name']),
            'statuses'     => collect(AppointmentStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
        ]);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment, AppointmentService $service): RedirectResponse
    {
        $data        = $request->validated();
        $scheduledAt = Carbon::parse($data['scheduled_at']);
        $vehicleId   = isset($data['vehicle_id']) ? (int) $data['vehicle_id'] : null;

        try {
            if ($vehicleId) {
                $service->validateNoOverlap($vehicleId, $scheduledAt, (int) $data['duration'], $appointment->id);
            }
        } catch (AppointmentOverlapException $e) {
            return back()->withErrors(['scheduled_at' => $e->getMessage()]);
        }

        $appointment->update([
            'customer_id'  => $data['customer_id'],
            'vehicle_id'   => $vehicleId,
            'scheduled_at' => $scheduledAt,
            'duration'     => (int) $data['duration'],
            'status'       => AppointmentStatus::from($data['status'] ?? $appointment->status->value),
            'notes'        => $data['notes'] ?? null,
        ]);

        $appointment->serviceTypes()->sync($data['service_type_ids'] ?? []);

        return redirect()->route('appointments.show', $appointment)
            ->with('success', 'Rendez-vous mis à jour.');
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        $this->authorize('delete', $appointment);
        $appointment->delete();

        return redirect()->route('appointments.index')
            ->with('success', 'Rendez-vous supprimé.');
    }

    public function cancel(Appointment $appointment, AppointmentService $service): RedirectResponse
    {
        $service->cancel($appointment);

        return redirect()->route('appointments.show', $appointment)
            ->with('success', 'Rendez-vous annulé.');
    }

    /**
     * Return today's appointments as JSON for calendar rendering.
     * Ordered by scheduled_at ascending.
     */
    public function today(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Appointment::class);

        $appointments = Appointment::query()
            ->with('customer:id,first_name,last_name', 'vehicle:id,license_plate,make,model', 'serviceTypes:id,name')
            ->whereDate('scheduled_at', today())
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn ($apt) => [
                'id'           => $apt->id,
                'start'        => $apt->scheduled_at->toIso8601String(),
                'end'          => $apt->ends_at?->toIso8601String(),
                'duration'     => $apt->duration,
                'status'       => $apt->status->value,
                'status_label' => $apt->status->label(),
                'notes'        => $apt->notes,
                'customer'     => [
                    'id'   => $apt->customer->id,
                    'name' => $apt->customer->first_name . ' ' . $apt->customer->last_name,
                ],
                'vehicle'      => $apt->vehicle ? [
                    'id'    => $apt->vehicle->id,
                    'plate' => $apt->vehicle->license_plate,
                    'make'  => $apt->vehicle->make,
                    'model' => $apt->vehicle->model,
                ] : null,
                'services'     => $apt->expectedServices,
            ]);

        return response()->json($appointments);
    }
}
