import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';

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

function Chip({ active, color, children, onClick }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium transition ${
                active
                    ? 'border-primary bg-primary text-primary-foreground'
                    : 'border-border bg-card text-foreground hover:bg-accent'
            }`}
        >
            {color && !active && (
                <span className={`h-2 w-2 rounded-full ${BADGE[color] ?? BADGE.gray}`} />
            )}
            {children}
        </button>
    );
}

export default function Index({ orders, statuses, total, can }) {
    const [status, setStatus] = useState('all');
    const [query, setQuery] = useState('');

    const filtered = useMemo(() => {
        const q = query.trim().toLowerCase();
        return orders.filter((o) => {
            if (status !== 'all' && o.status !== status) return false;
            if (!q) return true;
            return (
                o.number.toLowerCase().includes(q) ||
                (o.customer ?? '').toLowerCase().includes(q) ||
                (o.vehicle ?? '').toLowerCase().includes(q)
            );
        });
    }, [orders, status, query]);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-foreground">Atelier</h2>
                    {can.create && (
                        <Button asChild>
                            <Link href={route('workshop.create')}>+ Nouvel ordre de service</Link>
                        </Button>
                    )}
                </div>
            }
        >
            <Head title="Atelier" />

            <div className="py-8">
                <div className="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">
                    {/* Phase counters / filters */}
                    <div className="flex flex-wrap gap-2">
                        <Chip active={status === 'all'} onClick={() => setStatus('all')}>
                            Tous ({total})
                        </Chip>
                        {statuses.map((s) => (
                            <Chip
                                key={s.value}
                                active={status === s.value}
                                color={s.color}
                                onClick={() => setStatus(s.value)}
                            >
                                {s.label} ({s.count})
                            </Chip>
                        ))}
                    </div>

                    {/* Search */}
                    <TextInput
                        id="workshop-search"
                        name="search"
                        type="search"
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="Rechercher par n°, client ou immatriculation…"
                        className="block w-full"
                        aria-label="Rechercher un ordre de service"
                    />

                    {/* List */}
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        {filtered.length === 0 ? (
                            <p className="p-6 text-sm text-muted-foreground">
                                {orders.length === 0
                                    ? 'Aucun ordre de service actif.'
                                    : 'Aucun résultat pour ce filtre.'}
                            </p>
                        ) : (
                            <ul className="divide-y divide-border">
                                {filtered.map((o) => (
                                    <li key={o.id}>
                                        <Link
                                            href={route('workshop.show', o.id)}
                                            className="flex items-center justify-between gap-4 px-4 py-4 hover:bg-accent/50 sm:px-6"
                                        >
                                            <div className="min-w-0">
                                                <p className="font-medium text-foreground">
                                                    {o.number}
                                                </p>
                                                <p className="truncate text-sm text-muted-foreground">
                                                    {o.customer} · {o.vehicle}
                                                </p>
                                            </div>
                                            <div className="flex shrink-0 flex-col items-end gap-1">
                                                <span
                                                    className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${BADGE[o.status_color] ?? BADGE.gray}`}
                                                >
                                                    {o.status_label}
                                                </span>
                                                {o.updated_at && (
                                                    <span className="text-xs text-muted-foreground">
                                                        {o.updated_at}
                                                    </span>
                                                )}
                                            </div>
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
