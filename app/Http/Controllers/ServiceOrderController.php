<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\ServiceOrderStatus;
use App\Http\Requests\StoreServiceOrderRequest;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\ServiceOrder;
use App\Models\Vehicle;
use App\Services\ServiceOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ServiceOrder::class);

        $orders = ServiceOrder::query()
            ->active()
            ->with(['customer', 'vehicle'])
            ->latest('received_at')
            ->get()
            ->map(fn (ServiceOrder $o) => $this->summary($o));

        return Inertia::render('Workshop/Index', [
            'orders'   => $orders,
            'statuses' => collect(ServiceOrderStatus::cases())
                ->map(fn ($s) => ['value' => $s->value, 'label' => $s->label(), 'color' => $s->color()]),
            'can'      => ['create' => $request->user()->can('create', ServiceOrder::class)],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ServiceOrder::class);

        return Inertia::render('Workshop/Create', [
            'customers' => Customer::orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'vehicles'  => Vehicle::orderBy('license_plate')->get(['id', 'customer_id', 'license_plate', 'make', 'model']),
            'todayAppointments' => Appointment::query()
                ->whereDate('scheduled_at', today())
                ->where('status', AppointmentStatus::Confirmed->value)
                ->orderBy('scheduled_at')
                ->get(['id', 'customer_id', 'scheduled_at'])
                ->map(fn (Appointment $a) => [
                    'id'          => $a->id,
                    'customer_id' => $a->customer_id,
                    'time'        => $a->scheduled_at->format('H:i'),
                ]),
        ]);
    }

    public function store(StoreServiceOrderRequest $request, ServiceOrderService $service): RedirectResponse
    {
        $order = $service->create($request->validated(), $request->user());

        return redirect()->route('workshop.show', $order)
            ->with('success', "Ordre de service {$order->number} créé.");
    }

    public function show(ServiceOrder $serviceOrder): Response
    {
        $this->authorize('view', $serviceOrder);

        $serviceOrder->load(['customer', 'vehicle', 'items', 'appointment']);

        return Inertia::render('Workshop/Show', [
            'order' => [
                ...$this->summary($serviceOrder),
                'mileage_in'          => $serviceOrder->mileage_in,
                'reception_condition' => $serviceOrder->reception_condition,
                'internal_notes'      => $serviceOrder->internal_notes,
                'received_at'         => $serviceOrder->received_at?->format('d/m/Y H:i'),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(ServiceOrder $order): array
    {
        return [
            'id'           => $order->id,
            'number'       => $order->number,
            'status'       => $order->status->value,
            'status_label' => $order->status->label(),
            'status_color' => $order->status->color(),
            'customer'     => $order->customer
                ? $order->customer->first_name . ' ' . $order->customer->last_name
                : null,
            'vehicle'      => $order->vehicle
                ? trim($order->vehicle->license_plate . ' — ' . $order->vehicle->make . ' ' . $order->vehicle->model)
                : null,
        ];
    }
}
