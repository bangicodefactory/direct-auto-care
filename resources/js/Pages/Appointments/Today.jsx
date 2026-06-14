import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { LogIn } from 'lucide-react';
import { useEffect, useState } from 'react';

const REFRESH_MS = 5 * 60 * 1000;

const STATUS_STYLES = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};

function todayLabel() {
    return new Date().toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function currentHHMM() {
    const now = new Date();
    return `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
}

export default function AppointmentsToday({ todayList }) {
    useEffect(() => {
        function refresh() {
            router.reload({ only: ['todayList'] });
        }

        const timer = window.setInterval(refresh, REFRESH_MS);
        window.addEventListener('focus', refresh);

        return () => {
            window.clearInterval(timer);
            window.removeEventListener('focus', refresh);
        };
    }, []);

    const now = currentHHMM();

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-foreground">
                            Rendez-vous du jour
                        </h2>
                        <p className="mt-0.5 text-sm capitalize text-muted-foreground">
                            {todayLabel()}
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={route('appointments.index')}>← Calendrier</Link>
                    </Button>
                </div>
            }
        >
            <Head title="Rendez-vous du jour" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    {todayList.length === 0 ? (
                        <div className="rounded-lg border border-border bg-card p-12 text-center shadow-sm">
                            <p className="text-muted-foreground">Aucun rendez-vous aujourd'hui.</p>
                        </div>
                    ) : (
                        <div className="space-y-3">
                            {todayList.map((apt) => (
                                <AppointmentRow key={apt.id} apt={apt} now={now} />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function AppointmentRow({ apt, now }) {
    const [checkingIn, setCheckingIn] = useState(false);

    const isActive =
        apt.status !== 'cancelled' &&
        apt.scheduled_at <= now &&
        apt.ends_at !== null &&
        apt.ends_at > now;
    const isPast = apt.ends_at !== null && apt.ends_at <= now;
    const dimmed = apt.status === 'cancelled' || (isPast && apt.status !== 'completed');

    // Check-in is offered only for a confirmed appointment that has a vehicle and
    // hasn't already been turned into a service order.
    const canCheckIn = apt.status === 'confirmed' && apt.vehicle && !apt.service_order_id;

    function checkIn() {
        router.post(
            route('appointments.check-in', apt.id),
            {},
            {
                onStart: () => setCheckingIn(true),
                onFinish: () => setCheckingIn(false),
            },
        );
    }

    return (
        <div
            className={`flex items-start gap-4 rounded-lg border border-border bg-card p-4 shadow-sm transition hover:shadow-md ${dimmed ? 'opacity-60' : ''}`}
        >
            <Link
                href={route('appointments.show', apt.id)}
                className="flex min-w-0 flex-1 items-start gap-4"
            >
                <div className="w-14 flex-shrink-0 text-center">
                    <p className="text-sm font-semibold text-foreground">{apt.scheduled_at}</p>
                    {apt.ends_at && <p className="text-xs text-muted-foreground">{apt.ends_at}</p>}
                </div>

                <div
                    className={`mt-1 h-10 w-1 flex-shrink-0 rounded-full ${isActive ? 'bg-primary' : 'bg-border'}`}
                />

                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="text-sm font-medium text-foreground">
                            {apt.customer.name}
                        </span>
                        {apt.vehicle && (
                            <span className="text-xs text-muted-foreground">
                                {apt.vehicle.plate}
                            </span>
                        )}
                        <span
                            className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${STATUS_STYLES[apt.status] ?? 'bg-muted text-foreground'}`}
                        >
                            {apt.status_label}
                        </span>
                    </div>
                    {apt.services.length > 0 && (
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            {apt.services.join(' · ')}
                        </p>
                    )}
                    {apt.notes && (
                        <p className="mt-0.5 truncate text-xs text-muted-foreground">{apt.notes}</p>
                    )}
                </div>
            </Link>

            <div className="flex flex-shrink-0 flex-col items-end gap-2">
                <span className="text-xs text-muted-foreground">{apt.duration} min</span>
                {canCheckIn && (
                    <button
                        type="button"
                        onClick={checkIn}
                        disabled={checkingIn}
                        className="inline-flex items-center gap-1.5 rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground transition hover:bg-primary/90 disabled:opacity-50"
                    >
                        <LogIn className="h-3.5 w-3.5" />
                        Check-in
                    </button>
                )}
            </div>
        </div>
    );
}
