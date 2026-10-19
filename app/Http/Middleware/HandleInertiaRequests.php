<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use App\Support\Branding;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'role' => $request->user()?->getRoleNames()->first(),
            ],
            'flash' => [
                'success'      => $request->session()->get('success'),
                'error'        => $request->session()->get('error'),
                'importReport' => $request->session()->get('importReport'),
            ],
            'appSettings' => [
                'company_name'       => config('company.name'),
                'logo_url'           => Branding::logoUrl(),
                'show_product_units' => (bool) config('company.show_product_units'),
            ],
            'notifications' => fn () => $request->user()
                ? $request->user()->notifications()->latest()->take(10)->get()->map(fn ($n) => [
                    'id'         => $n->id,
                    'type'       => $n->data['type'],
                    'message'    => $n->data['message'],
                    'url'        => $n->data['url'] ?? null,
                    'read_at'    => $n->read_at,
                    'created_at' => $n->created_at->diffForHumans(),
                ])
                : [],
            'unreadCount'  => fn () => $request->user()?->unreadNotifications()->count() ?? 0,
            // Drives the "Notes flotte" menu entry: hidden until a client is flagged.
            'fleetNotesEnabled' => fn () => $request->user() !== null && Customer::fleetNotes()->exists(),
            'locale'       => fn () => app()->getLocale(),
            'translations' => fn () => ['ui' => trans('ui')],
        ];
    }
}
