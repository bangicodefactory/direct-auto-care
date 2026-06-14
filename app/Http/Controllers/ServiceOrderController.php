<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\ServiceOrderStatus;
use App\Http\Requests\StoreServiceOrderRequest;
use App\Http\Requests\UpdateServiceOrderRequest;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Product;
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

    public function show(Request $request, ServiceOrder $serviceOrder): Response
    {
        $this->authorize('view', $serviceOrder);

        $serviceOrder->load(['customer', 'vehicle', 'items', 'quote', 'invoice']);

        $next = $serviceOrder->status->nextStatus();

        return Inertia::render('Workshop/Show', [
            'order' => [
                ...$this->summary($serviceOrder),
                'mileage_in'          => $serviceOrder->mileage_in,
                'fuel_level'          => $serviceOrder->fuel_level,
                'reception_condition' => $serviceOrder->reception_condition,
                'reception_photos'    => $serviceOrder->receptionPhotoUrls(),
                'signature_url'       => $serviceOrder->signatureUrl(),
                'diagnosis_notes'     => $serviceOrder->diagnosis_notes,
                'internal_notes'      => $serviceOrder->internal_notes,
                'received_at'         => $serviceOrder->received_at?->format('d/m/Y H:i'),
                'cancelled_at'        => $serviceOrder->cancelled_at?->format('d/m/Y H:i'),
                'items'               => $serviceOrder->items->map(fn ($item) => [
                    'id'            => $item->id,
                    'product_id'    => $item->product_id,
                    'description'   => $item->description,
                    'quantity'      => $item->quantity,
                    'unit_price_ht' => $item->unit_price_ht,
                    'line_total_ht' => $item->line_total_ht,
                ]),
                'quote'   => $serviceOrder->quote
                    ? ['id' => $serviceOrder->quote->id, 'number' => $serviceOrder->quote->number]
                    : null,
                'invoice' => $serviceOrder->invoice
                    ? ['id' => $serviceOrder->invoice->id, 'number' => $serviceOrder->invoice->number]
                    : null,
            ],
            // The 8-phase stepper: each phase with the timestamp it was entered.
            'phases' => collect(ServiceOrderStatus::workflow())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
                'at'    => $serviceOrder->{$s->timestampColumn()}?->format('d/m/Y H:i'),
            ]),
            'next' => $next ? ['value' => $next->value, 'label' => $next->label()] : null,
            'can'  => [
                'update' => $canUpdate = $request->user()->can('update', $serviceOrder),
            ],
            // Catalogue for the parts picker — only sent while parts are editable.
            'products' => ($canUpdate && $serviceOrder->status === ServiceOrderStatus::InProgress)
                ? Product::orderBy('name')->get(['id', 'name', 'sku', 'price_sell', 'quantity_on_hand'])
                    ->map(fn (Product $p) => [
                        'id'               => $p->id,
                        'name'             => $p->name,
                        'sku'              => $p->sku,
                        'price_sell'       => $p->price_sell,
                        'quantity_on_hand' => $p->quantity_on_hand,
                    ])
                : [],
        ]);
    }

    public function update(UpdateServiceOrderRequest $request, ServiceOrder $serviceOrder): RedirectResponse
    {
        $serviceOrder->update($request->validated());

        return back()->with('success', 'Ordre de service mis à jour.');
    }

    public function advance(ServiceOrder $serviceOrder): RedirectResponse
    {
        $this->authorize('update', $serviceOrder);

        $next = $serviceOrder->status->nextStatus();
        abort_if($next === null, 422, 'Aucune transition disponible.');

        $serviceOrder->update([
            'status'                  => $next->value,
            $next->timestampColumn()  => now(),
        ]);

        return back()->with('success', "Statut mis à jour : {$next->label()}.");
    }

    public function cancel(ServiceOrder $serviceOrder): RedirectResponse
    {
        $this->authorize('update', $serviceOrder);

        // A closed or already-cancelled order is terminal — can't be (re-)cancelled.
        abort_if(in_array($serviceOrder->status, [
            ServiceOrderStatus::Closed,
            ServiceOrderStatus::Cancelled,
        ], true), 422, 'Cet ordre ne peut plus être annulé.');

        $serviceOrder->update([
            'status'       => ServiceOrderStatus::Cancelled->value,
            'cancelled_at' => now(),
        ]);

        return back()->with('success', "Ordre de service {$serviceOrder->number} annulé.");
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
