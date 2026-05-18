<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ReminderStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const TTL_SECONDS = 300; // 5 minutes

    public function getKpis(User $user): array
    {
        $role = $user->hasRole('admin') ? 'admin' : 'employee';

        return Cache::remember("dashboard.{$role}", self::TTL_SECONDS, function () use ($role) {
            $kpis = $this->baseKpis();

            if ($role === 'admin') {
                $kpis = array_merge($kpis, $this->adminKpis());
            }

            return $kpis;
        });
    }

    private function baseKpis(): array
    {
        $now            = Carbon::now();
        $startOfMonth   = $now->copy()->startOfMonth();
        $startLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endLastMonth   = $now->copy()->subMonth()->endOfMonth();

        $thisMonth = Customer::where('created_at', '>=', $startOfMonth)->count();
        $lastMonth = Customer::whereBetween('created_at', [$startLastMonth, $endLastMonth])->count();

        return [
            'customers_total'      => Customer::count(),
            'customers_this_month' => $thisMonth,
            'customers_delta'      => $thisMonth - $lastMonth,
            'active_vehicles'      => Vehicle::count(),
            'pending_reminders'    => Reminder::where('status', ReminderStatus::Pending)->count(),
            'appointments_today'   => $this->appointmentsToday(),
            'low_stock'            => $this->lowStock(),
        ];
    }

    private function adminKpis(): array
    {
        return [
            'revenue_month'     => $this->revenue('month'),
            'revenue_year'      => $this->revenue('year'),
            'revenue_last_year' => $this->revenue('last_year'),
            'top_services'      => $this->topServices(),
            'monthly_stats'     => $this->monthlyStats(),
        ];
    }

    // -------------------------------------------------------------------------
    // Revenue — cancelled and draft invoices excluded
    // -------------------------------------------------------------------------

    private function revenue(string $period): float
    {
        $now = Carbon::now();

        $query = Invoice::whereIn('status', [InvoiceStatus::Issued, InvoiceStatus::Paid])
            ->where('type', 'invoice');

        return (float) match ($period) {
            'month'     => (clone $query)->whereYear('issue_date', $now->year)
                ->whereMonth('issue_date', $now->month)
                ->sum('total_ttc'),
            'year'      => (clone $query)->whereYear('issue_date', $now->year)->sum('total_ttc'),
            'last_year' => (clone $query)->whereYear('issue_date', $now->year - 1)->sum('total_ttc'),
        };
    }

    // -------------------------------------------------------------------------
    // Top services — bar chart, top 5 by service record count
    // -------------------------------------------------------------------------

    private function topServices(): array
    {
        return DB::table('service_record_service_type')
            ->join('service_types', 'service_types.id', '=', 'service_record_service_type.service_type_id')
            ->select('service_types.name', DB::raw('COUNT(*) as count'))
            ->groupBy('service_types.id', 'service_types.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'count' => (int) $row->count])
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // Monthly stats — line chart, last 12 months
    // -------------------------------------------------------------------------

    private function monthlyStats(): array
    {
        $cutoff = Carbon::now()->subMonths(11)->startOfMonth();

        $revenue = Invoice::whereIn('status', [InvoiceStatus::Issued, InvoiceStatus::Paid])
            ->where('type', 'invoice')
            ->where('issue_date', '>=', $cutoff)
            ->selectRaw('YEAR(issue_date) AS yr, MONTH(issue_date) AS mo, SUM(total_ttc) AS total')
            ->groupByRaw('YEAR(issue_date), MONTH(issue_date)')
            ->get()
            ->keyBy(fn ($r) => "{$r->yr}-{$r->mo}");

        $appointments = Appointment::where('scheduled_at', '>=', $cutoff)
            ->selectRaw('YEAR(scheduled_at) AS yr, MONTH(scheduled_at) AS mo, COUNT(*) AS cnt')
            ->groupByRaw('YEAR(scheduled_at), MONTH(scheduled_at)')
            ->get()
            ->keyBy(fn ($r) => "{$r->yr}-{$r->mo}");

        return collect(range(0, 11))
            ->map(function (int $i) use ($revenue, $appointments) {
                $month = Carbon::now()->subMonths(11 - $i)->startOfMonth();
                $key   = "{$month->year}-{$month->month}";

                return [
                    'month'        => $month->format('Y-m'),
                    'revenue'      => (float) ($revenue[$key]->total ?? 0),
                    'appointments' => (int) ($appointments[$key]->cnt ?? 0),
                ];
            })
            ->values()
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // Today's appointments
    // -------------------------------------------------------------------------

    private function appointmentsToday(): array
    {
        return Appointment::with(['customer', 'vehicle', 'serviceTypes'])
            ->whereDate('scheduled_at', today())
            ->where('status', '!=', AppointmentStatus::Cancelled)
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (Appointment $a) => [
                'id'           => $a->id,
                'customer'     => $a->customer->first_name . ' ' . $a->customer->last_name,
                'vehicle'      => $a->vehicle->license_plate . ' (' . $a->vehicle->make . ' ' . $a->vehicle->model . ')',
                'scheduled_at' => $a->scheduled_at->format('H:i'),
                'status'       => $a->status->value,
                'services'     => $a->serviceTypes->pluck('name')->all(),
            ])
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // Low-stock products
    // -------------------------------------------------------------------------

    private function lowStock(): array
    {
        return Product::lowStock()
            ->orderBy('quantity_on_hand')
            ->limit(10)
            ->get(['id', 'name', 'quantity_on_hand', 'low_stock_threshold', 'unit'])
            ->toArray();
    }
}
