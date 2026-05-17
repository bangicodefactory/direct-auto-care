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
        ];
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $type = InvoiceType::tryFrom($request->string('type')->value());

        $invoices = Invoice::query()
            ->with('customer:id,first_name,last_name')
            ->when($type, fn ($q) => $q->where('type', $type->value))
            ->when(
                $request->filled('status') && InvoiceStatus::tryFrom($request->status),
                fn ($q) => $q->where('status', $request->status)
            )
            ->when(
                $request->string('search')->trim()->value(),
                fn ($q, $search) => $q->where(function ($inner) use ($search) {
                    $inner->where('number', 'LIKE', "%{$search}%")
                          ->orWhereHas('customer', fn ($c) => $c->where('first_name', 'LIKE', "%{$search}%")
                              ->orWhere('last_name', 'LIKE', "%{$search}%"));
                })
            )
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
                    ? ['id' => $inv->customer->id, 'name' => $inv->customer->first_name . ' ' . $inv->customer->last_name]
                    : null,
            ]);

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters'  => $request->only(['type', 'status', 'search']),
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
            'customers' => Customer::orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'products'  => Product::orderBy('name')->get(['id', 'name', 'unit', 'price_sell']),
            'types'     => collect(InvoiceType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
            'defaultType' => $request->string('type')->value() ?: InvoiceType::Invoice->value,
        ]);
    }

    public function store(StoreInvoiceRequest $request, InvoiceService $service): RedirectResponse
    {
        try {
            $invoice = $service->create($request->validated(), $request->user());
        } catch (InsufficientStockException $e) {
            return back()->withErrors(['items' => $e->getMessage()]);
        }

        $label = $invoice->type === InvoiceType::Quote ? 'Devis' : 'Facture';

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "{$label} {$invoice->number} créé(e) avec succès.");
    }

    public function show(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'vehicle', 'items.product', 'creator']);

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
                    'name'  => $invoice->customer->first_name . ' ' . $invoice->customer->last_name,
                    'phone' => $invoice->customer->phone,
                    'email' => $invoice->customer->email,
                ] : null,
                'vehicle'     => $invoice->vehicle ? [
                    'id'           => $invoice->vehicle->id,
                    'plate'        => $invoice->vehicle->plate,
                    'make'         => $invoice->vehicle->make,
                    'model'        => $invoice->vehicle->model,
                ] : null,
                'items'       => $invoice->items->map(fn ($item) => [
                    'id'            => $item->id,
                    'product_id'    => $item->product_id,
                    'description'   => $item->description,
                    'quantity'      => $item->quantity,
                    'unit_price_ht' => $item->unit_price_ht,
                    'line_total_ht' => $item->line_total_ht,
                ]),
                'created_by'  => $invoice->creator?->name,
            ],
            'can' => [
                'edit'   => auth()->user()->can('update', $invoice),
                'delete' => auth()->user()->can('delete', $invoice),
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
                    'description'   => $item->description,
                    'quantity'      => $item->quantity,
                    'unit_price_ht' => $item->unit_price_ht,
                ]),
            ],
            'customers' => Customer::orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'products'  => Product::orderBy('name')->get(['id', 'name', 'unit', 'price_sell']),
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice, InvoiceService $service): RedirectResponse
    {
        try {
            $service->update($invoice, $request->validated(), $request->user());
        } catch (InsufficientStockException $e) {
            return back()->withErrors(['items' => $e->getMessage()]);
        }

        $label = $invoice->type === InvoiceType::Quote ? 'Devis' : 'Facture';

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "{$label} {$invoice->number} mis(e) à jour.");
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Document supprimé.');
    }
}
