<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
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
        $settings = app(SettingsService::class)->get();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'role' => $request->user()?->getRoleNames()->first(),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
            ],
            'appSettings' => [
                'company_name' => $settings->company_name,
                'logo_url'     => $settings->logoUrl(),
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
            'unreadCount' => fn () => $request->user()?->unreadNotifications()->count() ?? 0,
        ];
    }
}
