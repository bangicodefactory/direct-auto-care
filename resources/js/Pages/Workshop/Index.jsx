import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

const BADGE = {
    gray: 'bg-muted text-foreground',
    blue: 'bg-blue-100 text-blue-700',
    yellow: 'bg-yellow-100 text-yellow-800',
    orange: 'bg-orange-100 text-orange-700',
    purple: 'bg-purple-100 text-purple-700',
    cyan: 'bg-cyan-100 text-cyan-700',
    green: 'bg-green-100 text-green-700',
    red: 'bg-red-100 text-red-700',
};

export default function Index({ orders, can }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-foreground">Atelier</h2>
                    {can.create && (
                        <Button asChild>
                            <Link href={route('workshop.create')}>+ Réception</Link>
                        </Button>
                    )}
                </div>
            }
        >
            <Head title="Atelier" />

            <div className="py-8">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        {orders.length === 0 ? (
                            <p className="p-6 text-sm text-muted-foreground">
                                Aucun ordre de service actif.
                            </p>
                        ) : (
                            <ul className="divide-y divide-border">
                                {orders.map((o) => (
                                    <li key={o.id}>
                                        <Link
                                            href={route('workshop.show', o.id)}
                                            className="flex items-center justify-between gap-4 px-6 py-4 hover:bg-accent/50"
                                        >
                                            <div>
                                                <p className="font-medium text-foreground">
                                                    {o.number}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {o.customer} · {o.vehicle}
                                                </p>
                                            </div>
                                            <span
                                                className={`inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${BADGE[o.status_color] ?? BADGE.gray}`}
                                            >
                                                {o.status_label}
                                            </span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
