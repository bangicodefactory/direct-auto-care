<?php

namespace App\Http\Controllers;

use App\Enums\ProductCategory;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Http\Requests\ImportProductsRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\Import\ProductImportService;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:create,' . Product::class, only: ['create', 'store', 'import', 'importTemplate']),
            new Middleware('can:update,product', only: ['edit', 'update', 'adjustStock']),
            new Middleware('can:delete,product', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::query()
            ->when(
                $request->filled('category') && ProductCategory::tryFrom($request->category),
                fn ($q) => $q->where('category', $request->category)
            )
            ->when($request->boolean('low_stock'), fn ($q) => $q->lowStock())
            ->when(
                $request->string('search')->trim()->value(),
                fn ($q, $search) => $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'LIKE', "%{$search}%")
                          ->orWhere('sku', 'LIKE', "%{$search}%");
                })
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($p) => [
                'id'                  => $p->id,
                'category'            => $p->category->value,
                'name'                => $p->name,
                'sku'                 => $p->sku,
                'unit'                => $p->unit,
                'price_sell'          => $p->price_sell,
                'quantity_on_hand'    => $p->quantity_on_hand,
                'low_stock_threshold' => $p->low_stock_threshold,
                'is_low_stock'        => $p->is_low_stock,
            ]);

        return Inertia::render('Products/Index', [
            'products'   => $products,
            'filters'    => (object) $request->only(['category', 'low_stock', 'search']),
            'categories' => collect(ProductCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'can'        => [
                'create' => $request->user()->can('create', Product::class),
                'import' => $request->user()->can('create', Product::class),
                'edit'   => $request->user()->hasRole('admin'),
                'delete' => $request->user()->hasRole('admin'),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('Products/Create', [
            'categories' => collect(ProductCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = Product::create($request->validated());

        return redirect()->route('products.show', $product)
            ->with('success', 'Produit créé avec succès.');
    }

    public function importTemplate(ProductImportService $service): BinaryFileResponse
    {
        // Serve the pre-built template (warmed at deploy time). Not deleted after
        // send — it is a persistent cache, not a per-request temp file.
        return response()->download($service->cachedTemplatePath(), ProductImportService::TEMPLATE_FILENAME);
    }

    public function import(ImportProductsRequest $request, ProductImportService $service): RedirectResponse
    {
        $report = $service->import($request->file('file'));

        return back()
            ->with('success', "{$report->importedCount()} produit(s) importé(s), {$report->failedCount()} en erreur.")
            ->with('importReport', $report->toArray());
    }

    public function show(Product $product): Response
    {
        $this->authorize('view', $product);

        $movements = $product->stockMovements()
            ->with('user:id,name')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($m) => [
                'id'         => $m->id,
                'type'       => $m->type->value,
                'quantity'   => $m->quantity,
                'reason'     => $m->reason,
                'user'       => $m->user?->name,
                'created_at' => $m->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Products/Show', [
            'product' => [
                'id'                  => $product->id,
                'category'            => $product->category->value,
                'name'                => $product->name,
                'sku'                 => $product->sku,
                'unit'                => $product->unit,
                'price_buy'           => $product->price_buy,
                'price_sell'          => $product->price_sell,
                'quantity_on_hand'    => $product->quantity_on_hand,
                'low_stock_threshold' => $product->low_stock_threshold,
                'is_low_stock'        => $product->is_low_stock,
            ],
            'movements' => $movements,
            'can'       => [
                'edit'   => auth()->user()->can('update', $product),
                'delete' => auth()->user()->can('delete', $product),
                'adjust' => auth()->user()->can('update', $product),
            ],
        ]);
    }

    public function adjustStock(StoreStockAdjustmentRequest $request, Product $product, StockService $stock): RedirectResponse
    {
        try {
            $stock->recordMovement(
                product: $product,
                type: StockMovementType::Adjustment,
                quantity: $request->integer('quantity'),
                reason: $request->string('reason')->trim()->value() ?: null,
                invoice: null,
                user: $request->user(),
            );
        } catch (InsufficientStockException $e) {
            if ($request->header('X-Inertia')) {
                return back()->withErrors(['quantity' => $e->getMessage()]);
            }

            throw $e; // Non-Inertia: let bootstrap/app.php return 422 JSON
        }

        return redirect()->route('products.show', $product)
            ->with('success', 'Ajustement de stock enregistré.');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Products/Edit', [
            'product' => [
                'id'                  => $product->id,
                'category'            => $product->category->value,
                'name'                => $product->name,
                'sku'                 => $product->sku,
                'unit'                => $product->unit,
                'price_buy'           => $product->price_buy,
                'price_sell'          => $product->price_sell,
                'quantity_on_hand'    => $product->quantity_on_hand,
                'low_stock_threshold' => $product->low_stock_threshold,
            ],
            'categories' => collect(ProductCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()->route('products.show', $product)
            ->with('success', 'Produit mis à jour avec succès.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Produit supprimé.');
    }
}
