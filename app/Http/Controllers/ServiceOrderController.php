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

        $status = $request->string('status')->toString();
        $search = $request->string('q')->trim()->toString();

        $orders = ServiceOrder::query()
            ->active()
            ->with(['customer', 'vehicle'])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%"))
                        ->orWhereHas('vehicle', fn ($v) => $v->where('license_plate', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(50)
            ->withQueryString()
            ->through(fn (ServiceOrder $o) => $this->summary($o));

        // Counts span the full active set (unfiltered) so the chips always show
        // true per-phase totals regardless of the current filter.
        $counts = ServiceOrder::query()
            ->active()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('Workshop/Index', [
            'orders'   => $orders,
            // Active-phase statuses only (Closed/Cancelled are excluded from the board),
            // each with its live count for the filter chips.
            'statuses' => collect(ServiceOrderStatus::cases())
                ->reject(fn ($s) => in_array($s, [ServiceOrderStatus::Closed, ServiceOrderStatus::Cancelled], true))
                ->map(fn ($s) => [
                    'value' => $s->value,
                    'label' => $s->label(),
                    'color' => $s->color(),
                    'count' => (int) ($counts[$s->value] ?? 0),
                ])
                ->values(),
            'total'    => (int) $counts->sum(),
            'filters'  => ['status' => $status, 'q' => $search],
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
        $order = $service->create(
            $request->safe()->except(['photos', 'signature']),
            $request->user(),
            $request->file('photos', []),
            $request->file('signature'),
        );

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
                'fuel_level'          => $serviceOrder->fuel_level,
                'reception_condition' => $serviceOrder->reception_condition,
                'reception_photos'    => $serviceOrder->receptionPhotoUrls(),
                'signature_url'       => $serviceOrder->signatureUrl(),
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
            'updated_at'   => $order->updated_at?->format('d/m/Y H:i'),
        ];
    }
}
