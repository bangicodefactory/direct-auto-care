<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class ServicesController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:create,' . Service::class, only: ['create', 'store']),
            new Middleware('can:update,service', only: ['edit', 'update']),
            new Middleware('can:delete,service', only: ['destroy']),
        ];
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Service::class);

        $services = Service::query()
            ->when(
                $request->string('search')->trim()->value(),
                fn ($q, $search) => $q->where('name', 'LIKE', "%{$search}%")
            )
            ->when($request->boolean('active_only'), fn ($q) => $q->active())
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($s) => [
                'id'               => $s->id,
                'name'             => $s->name,
                'description'      => $s->description,
                'default_price_ht' => $s->default_price_ht,
                'is_active'        => $s->is_active,
            ]);

        return Inertia::render('Services/Index', [
            'services' => $services,
            'filters'  => (object) $request->only(['search', 'active_only']),
            'can'      => [
                'create' => $request->user()->can('create', Service::class),
                'edit'   => $request->user()->hasRole('admin'),
                'delete' => $request->user()->hasRole('admin'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Services/Create');
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        Service::create($request->validated());

        return redirect()->route('services.index')
            ->with('success', 'Service créé avec succès.');
    }

    public function edit(Service $service): Response
    {
        return Inertia::render('Services/Edit', [
            'service' => [
                'id'               => $service->id,
                'name'             => $service->name,
                'description'      => $service->description,
                'default_price_ht' => $service->default_price_ht,
                'is_active'        => $service->is_active,
            ],
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($request->validated());

        return redirect()->route('services.index')
            ->with('success', 'Service mis à jour avec succès.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return redirect()->route('services.index')
            ->with('success', 'Service supprimé.');
    }
}
