<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service)
    {
    }

    public function __invoke(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'kpis'    => $this->service->getKpis($request->user()),
            'isAdmin' => $request->user()->hasRole('admin'),
        ]);
    }
}
