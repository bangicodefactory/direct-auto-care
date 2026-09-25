<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Service;
use App\Models\Vehicle;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class InvoicesController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:create,' . Invoice::class, only: ['create', 'store']),
            new Middleware('can:update,invoice', only: ['edit', 'update']),
            new Middleware('can:delete,invoice', only: ['destroy']),
            new Middleware('can:convert,invoice', only: ['convert']),
            new Middleware('can:issue,invoice', only: ['issue']),
            new Middleware('can:markPaid,invoice', only: ['markPaid']),
            new Middleware('can:cancel,invoice', only: ['cancel']),
        ];
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $type = InvoiceType::tryFrom($request->string('type')->value());

        $invoices = Invoice::query()
            ->with('customer:id,type,company_name,first_name,last_name')
            ->when($type, fn ($q) => $q->where('type', $type->value))
            ->when(
                InvoiceStatus::tryFrom($request->string('status')->value()),
                fn ($q, $status) => $q->where('status', $status->value)
            )
            ->when(
                $request->string('search')->trim()->value(),
                fn ($q, $search) => $q->where(function ($inner) use ($search) {
                    $inner->where('number', 'LIKE', "%{$search}%")
                          ->orWhereHas('customer', fn ($c) => $c->where('first_name', 'LIKE', "%{$search}%")
                              ->orWhere('last_name', 'LIKE', "%{$search}%")
                              ->orWhere('company_name', 'LIKE', "%{$search}%"));
                })
            )
            ->when($request->string('date_from')->trim()->value(), fn ($q, $d) => $q->whereDate('issue_date', '>=', $d))
            ->when($request->string('date_to')->trim()->value(), fn ($q, $d) => $q->whereDate('issue_date', '<=', $d))
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($inv) => [
                'id'         => $inv->id,
                'number'     => $inv->number,
                'type'       => $inv->type->value,
                'status'     => $inv->status->value,
                'issue_date' => $inv->issue_date->format('d/m/Y'),
                'total_ttc'  => $inv->total_ttc,
                'customer'   => $inv->customer
                    ? ['id' => $inv->customer->id, 'name' => $inv->customer->display_name]
                    : null,
            ]);

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters'  => (object) $request->only(['type', 'status', 'search', 'date_from', 'date_to']),
            'types'    => collect(InvoiceType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
            'statuses' => collect(InvoiceStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'can'      => [
                'create' => $request->user()->can('create', Invoice::class),
                'delete' => $request->user()->hasRole('admin'),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Invoice::class);

        return Inertia::render('Invoices/Create', [
            'customers'   => Customer::orderBy('last_name')->orderBy('first_name')->get(['id', 'type', 'company_name', 'first_name', 'last_name']),
            'products'    => Product::orderBy('name')->get(['id', 'name', 'unit', 'price_sell']),
            'services'    => Service::active()->orderBy('name')->get(['id', 'name', 'default_price_ht']),
            'vehicles'    => Vehicle::orderBy('license_plate')->get(['id', 'customer_id', 'license_plate', 'make', 'model']),
            'types'       => collect(InvoiceType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
            'defaultType' => InvoiceType::tryFrom($request->string('type')->value())?->value ?? InvoiceType::Invoice->value,
        ]);
    }

    public function store(StoreInvoiceRequest $request, InvoiceService $service): RedirectResponse
    {
        try {
            $invoice = $service->create($request->validated(), $request->user());
        } catch (InsufficientStockException $e) {
            return back()->withErrors(['items' => $e->getMessage()]);
        }

        [$label, $past] = $invoice->type === InvoiceType::Quote
            ? ['Devis', 'créé']
            : ['Facture', 'créée'];

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "{$label} {$invoice->number} {$past} avec succès.");
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'vehicle', 'items.product', 'items.vehicle', 'creator']);

        return Inertia::render('Invoices/Show', [
            'invoice' => [
                'id'          => $invoice->id,
                'number'      => $invoice->number,
                'type'        => $invoice->type->value,
                'status'      => $invoice->status->value,
                'issue_date'  => $invoice->issue_date->format('d/m/Y'),
                'due_date'    => $invoice->due_date?->format('d/m/Y'),
                'subtotal_ht' => $invoice->subtotal_ht,
                'discount'    => $invoice->discount,
                'tva_rate'    => $invoice->tva_rate,
                'tva_amount'  => $invoice->tva_amount,
                'total_ttc'   => $invoice->total_ttc,
                'notes'       => $invoice->notes,
                'customer'    => $invoice->customer ? [
                    'id'    => $invoice->customer->id,
                    'name'  => $invoice->customer->display_name,
                    'phone' => $invoice->customer->phone,
                    'email' => $invoice->customer->email,
                ] : null,
                'vehicle'     => $invoice->vehicle ? [
                    'id'           => $invoice->vehicle->id,
                    'plate'        => $invoice->vehicle->license_plate,
                    'make'         => $invoice->vehicle->make,
                    'model'        => $invoice->vehicle->model,
                ] : null,
                'items'       => $invoice->items->map(fn ($item) => [
                    'id'            => $item->id,
                    'product_id'    => $item->product_id,
                    'service_id'    => $item->service_id,
                    'description'   => $item->description,
                    'unit'          => $item->unit,
                    'quantity'      => $item->quantity,
                    'unit_price_ht' => $item->unit_price_ht,
                    'line_total_ht' => $item->line_total_ht,
                    'vehicle'       => $item->vehicle ? [
                        'id'    => $item->vehicle->id,
                        'plate' => $item->vehicle->license_plate,
                        'make'  => $item->vehicle->make,
                        'model' => $item->vehicle->model,
                    ] : null,
                ]),
                'created_by'  => $invoice->creator?->name,
            ],
            'can' => [
                'edit'     => $request->user()->can('update', $invoice),
                'delete'   => $request->user()->can('delete', $invoice),
                'convert'  => $request->user()->can('convert', $invoice),
                'issue'    => $request->user()->can('issue', $invoice),
                'markPaid' => $request->user()->can('markPaid', $invoice),
                'cancel'   => $request->user()->can('cancel', $invoice),
            ],
        ]);
    }

    public function edit(Invoice $invoice): Response
    {
        $invoice->load('items');

        return Inertia::render('Invoices/Edit', [
            'invoice'   => [
                'id'          => $invoice->id,
                'number'      => $invoice->number,
                'type'        => $invoice->type->value,
                'status'      => $invoice->status->value,
                'customer_id' => $invoice->customer_id,
                'vehicle_id'  => $invoice->vehicle_id,
                'issue_date'  => $invoice->issue_date->format('Y-m-d'),
                'due_date'    => $invoice->due_date?->format('Y-m-d'),
                'discount'    => $invoice->discount,
                'tva_rate'    => $invoice->tva_rate,
                'notes'       => $invoice->notes,
                'items'       => $invoice->items->map(fn ($item) => [
                    'id'            => $item->id,
                    'product_id'    => $item->product_id,
                    'service_id'    => $item->service_id,
                    'vehicle_id'    => $item->vehicle_id,
                    'description'   => $item->description,
                    'unit'          => $item->unit,
                    'quantity'      => $item->quantity,
                    'unit_price_ht' => $item->unit_price_ht,
                ]),
            ],
            'customers' => Customer::orderBy('last_name')->orderBy('first_name')->get(['id', 'type', 'company_name', 'first_name', 'last_name']),
            'products'  => Product::orderBy('name')->get(['id', 'name', 'unit', 'price_sell']),
            'services'  => Service::active()->orderBy('name')->get(['id', 'name', 'default_price_ht']),
            'vehicles'  => Vehicle::orderBy('license_plate')->get(['id', 'customer_id', 'license_plate', 'make', 'model']),
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice, InvoiceService $service): RedirectResponse
    {
        try {
            $service->update($invoice, $request->validated(), $request->user());
        } catch (InsufficientStockException $e) {
            return back()->withErrors(['items' => $e->getMessage()]);
        }

        [$label, $past] = $invoice->type === InvoiceType::Quote
            ? ['Devis', 'mis']
            : ['Facture', 'mise'];

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "{$label} {$invoice->number} {$past} à jour.");
    }

    public function issue(Invoice $invoice): RedirectResponse
    {
        $invoice->update(['status' => InvoiceStatus::Issued->value]);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "Facture {$invoice->number} émise.");
    }

    public function markPaid(Invoice $invoice): RedirectResponse
    {
        $invoice->update(['status' => InvoiceStatus::Paid->value]);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "Facture {$invoice->number} marquée comme payée.");
    }

    public function cancel(Request $request, Invoice $invoice, InvoiceService $service): RedirectResponse
    {
        $service->cancel($invoice, $request->user());

        [$label, $past] = $invoice->type === InvoiceType::Quote
            ? ['Devis', 'annulé']
            : ['Facture', 'annulée'];

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "{$label} {$invoice->number} {$past}.");
    }

    public function convert(Request $request, Invoice $invoice, InvoiceService $service): RedirectResponse
    {
        $invoice->load('items');

        try {
            $newInvoice = $service->create([
                'type'                    => InvoiceType::Invoice->value,
                'customer_id'             => $invoice->customer_id,
                'vehicle_id'              => $invoice->vehicle_id,
                'issue_date'              => now()->toDateString(),
                'due_date'                => $invoice->due_date?->toDateString(),
                'discount'                => $invoice->discount,
                'tva_rate'                => $invoice->tva_rate,
                'notes'                   => $invoice->notes,
                'converted_from_quote_id' => $invoice->id,
                'items'                   => $invoice->items->map(fn ($item) => [
                    'product_id'    => $item->product_id,
                    'service_id'    => $item->service_id,
                    'vehicle_id'    => $item->vehicle_id,
                    'description'   => $item->description,
                    'unit'          => $item->unit,
                    'quantity'      => $item->quantity,
                    'unit_price_ht' => $item->unit_price_ht,
                ])->all(),
            ], $request->user());
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.show', $newInvoice)
            ->with('success', "Facture {$newInvoice->number} créée depuis le devis {$invoice->number}.");
    }

    public function destroy(Request $request, Invoice $invoice, InvoiceService $service): RedirectResponse
    {
        $service->delete($invoice, $request->user());

        return redirect()->route('invoices.index')
            ->with('success', 'Document supprimé.');
    }
}
