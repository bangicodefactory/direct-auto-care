<?php

namespace App\Http\Controllers;

use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use App\Jobs\SendReminderJob;
use App\Models\Reminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class RemindersController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:trigger,reminder', only: ['trigger']),
            new Middleware('can:cancel,reminder', only: ['cancel']),
        ];
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Reminder::class);

        $reminders = Reminder::query()
            ->with(['customer', 'vehicle'])
            ->when($request->string('status')->trim()->value(), function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->string('type')->trim()->value(), function ($q, $type) {
                $q->where('type', $type);
            })
            ->when($request->string('date_from')->trim()->value(), function ($q, $date) {
                $q->where('scheduled_for', '>=', $date);
            })
            ->when($request->string('date_to')->trim()->value(), function ($q, $date) {
                $q->where('scheduled_for', '<=', $date);
            })
            ->orderByDesc('scheduled_for')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($r) => [
                'id'            => $r->id,
                'type'          => ['value' => $r->type->value, 'label' => $r->type->label()],
                'status'        => ['value' => $r->status->value, 'label' => $r->status->label()],
                'scheduled_for' => $r->scheduled_for->format('d/m/Y'),
                'sent_at'       => $r->sent_at?->format('d/m/Y H:i'),
                'error_message' => $r->error_message,
                'customer'      => [
                    'id'   => $r->customer->id,
                    'name' => $r->customer->first_name . ' ' . $r->customer->last_name,
                ],
                'vehicle' => [
                    'id'            => $r->vehicle->id,
                    'license_plate' => $r->vehicle->license_plate,
                ],
                'can' => [
                    'trigger' => in_array($r->status, [ReminderStatus::Pending, ReminderStatus::Cancelled]),
                    'cancel'  => $r->status === ReminderStatus::Pending,
                ],
            ]);

        return Inertia::render('Reminders/Index', [
            'reminders' => $reminders,
            'filters'   => (object) $request->only(['status', 'type', 'date_from', 'date_to']),
            'statuses'  => collect(ReminderStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'types'     => collect(ReminderType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
        ]);
    }

    public function trigger(Reminder $reminder): RedirectResponse
    {
        dispatch_sync(new SendReminderJob($reminder));

        return redirect()->route('reminders.index')
            ->with('success', 'Rappel envoyé.');
    }

    public function cancel(Reminder $reminder): RedirectResponse
    {
        $reminder->update(['status' => ReminderStatus::Cancelled]);

        return redirect()->route('reminders.index')
            ->with('success', 'Rappel annulé.');
    }
}
