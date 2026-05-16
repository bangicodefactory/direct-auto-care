<?php

namespace App\Http\Controllers;

use App\Enums\ProductCategory;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class ProductsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:create,' . Product::class, only: ['create', 'store']),
            new Middleware('can:update,product', only: ['edit', 'update']),
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
            'filters'    => $request->only(['category', 'low_stock', 'search']),
            'categories' => ProductCategory::cases(),
            'can'        => [
                'create' => $request->user()->can('create', Product::class),
                'delete' => $request->user()->hasRole('admin'),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('Products/Create', [
            'categories' => ProductCategory::cases(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = Product::create($request->validated());

        return redirect()->route('products.show', $product)
            ->with('success', 'Produit créé avec succès.');
    }

    public function show(Product $product): Response
    {
        $this->authorize('view', $product);

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
            'can' => [
                'edit'   => auth()->user()->can('update', $product),
                'delete' => auth()->user()->can('delete', $product),
            ],
        ]);
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
            'categories' => ProductCategory::cases(),
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
