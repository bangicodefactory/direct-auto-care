import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Paginated } from '@/types/pagination';
import { Head, Link, router } from '@inertiajs/react';
import { NotebookPen } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface FleetVehicleRow {
    id: number;
    license_plate: string;
    make: string;
    model: string;
    year: number | null;
    customer: string;
    in_shop: boolean;
    notes_count: number;
    latest_note: { excerpt: string; created_at: string } | null;
}

interface FleetCustomer {
    id: number;
    display_name: string;
}

interface FleetNotesFilters {
    search?: string;
    customer?: string;
}

interface IndexProps {
    vehicles: Paginated<FleetVehicleRow>;
    customers: FleetCustomer[];
    filters: FleetNotesFilters;
}

export default function Index({ vehicles, customers, filters }: IndexProps) {
    const [search, setSearch] = useState(filters?.search ?? '');
    const isFirstRender = useRef(true);
    const customer = filters?.customer ?? '';

    function reload(params: { search?: string; customer?: string }) {
        router.get(
            route('fleet-notes.index'),
            {
                search: params.search || undefined,
                customer: params.customer || undefined,
            },
            { preserveState: true, replace: true },
        );
    }

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }
        const timer = setTimeout(() => reload({ search, customer }), 300);
        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-foreground">
                    Notes flotte
                </h2>
            }
        >
            <Head title="Notes flotte" />

            <div className="py-8">
                <div className="px-4 sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        {/* TOOLBAR */}
                        <div className="space-y-3 border-b border-border p-4">
                            <div className="flex flex-wrap items-center gap-3">
                                <TextInput
                                    className="flex-1"
                                    placeholder="Rechercher par plaque, marque ou modèle…"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                                {search && (
                                    <Button variant="outline" onClick={() => setSearch('')}>
                                        Effacer
                                    </Button>
                                )}
                            </div>

                            {customers.length > 1 && (
                                <div className="flex flex-wrap gap-2">
                                    {[{ id: 0, display_name: 'Tous' }, ...customers].map((c) => {
                                        const value = c.id === 0 ? '' : String(c.id);
                                        const active = customer === value;
                                        return (
                                            <button
                                                key={c.id}
                                                type="button"
                                                onClick={() => reload({ search, customer: value })}
                                                className={`inline-flex min-h-[36px] items-center rounded-full border px-3 text-xs font-medium transition ${
                                                    active
                                                        ? 'border-primary bg-primary text-primary-foreground'
                                                        : 'border-border bg-card text-foreground hover:bg-accent'
                                                }`}
                                            >
                                                {c.display_name}
                                            </button>
                                        );
                                    })}
                                </div>
                            )}
                        </div>

                        {/* VEHICLES */}
                        <ul className="divide-y divide-border">
                            {vehicles.data.map((v) => (
                                <li key={v.id}>
                                    <Link
                                        href={route('fleet-notes.show', v.id)}
                                        className="flex min-h-[44px] flex-col gap-2 p-4 transition hover:bg-accent sm:flex-row sm:items-start sm:justify-between"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="font-mono font-medium text-foreground">
                                                    {v.license_plate}
                                                </span>
                                                <span className="text-sm text-foreground">
                                                    {v.make} {v.model}
                                                    {v.year ? ` (${v.year})` : ''}
                                                </span>
                                                {v.in_shop && (
                                                    <span className="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700">
                                                        À l&apos;atelier
                                                    </span>
                                                )}
                                            </div>
                                            <p className="mt-0.5 text-xs text-muted-foreground">
                                                {v.customer}
                                            </p>
                                            {v.latest_note ? (
                                                <p className="mt-2 line-clamp-2 text-sm text-muted-foreground">
                                                    <span className="font-medium text-foreground">
                                                        {v.latest_note.created_at}
                                                    </span>{' '}
                                                    — {v.latest_note.excerpt}
                                                </p>
                                            ) : (
                                                <p className="mt-2 text-sm italic text-muted-foreground">
                                                    Aucune note pour l&apos;instant.
                                                </p>
                                            )}
                                        </div>
                                        <span className="inline-flex shrink-0 items-center gap-1.5 text-sm text-muted-foreground">
                                            <NotebookPen className="h-4 w-4" />
                                            {v.notes_count} {v.notes_count > 1 ? 'notes' : 'note'}
                                        </span>
                                    </Link>
                                </li>
                            ))}
                            {vehicles.data.length === 0 && (
                                <li className="py-8 text-center text-sm text-muted-foreground">
                                    Aucun véhicule de flotte trouvé.
                                </li>
                            )}
                        </ul>

                        {/* PAGINATION */}
                        {vehicles.links.length > 3 && (
                            <div className="flex items-center justify-between border-t border-border px-6 py-3">
                                {vehicles.links.map((link, i) =>
                                    link.url ? (
                                        <Link
                                            key={i}
                                            href={link.url}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className={`inline-flex min-h-[44px] items-center px-3 text-sm ${link.active ? 'font-bold text-primary' : 'text-muted-foreground hover:text-foreground'}`}
                                        />
                                    ) : (
                                        <span
                                            key={i}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className="inline-flex min-h-[44px] items-center px-3 text-sm text-muted-foreground/40"
                                        />
                                    ),
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
