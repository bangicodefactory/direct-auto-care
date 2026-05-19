import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const TYPE_STYLES = {
    invoice: 'bg-blue-100 text-blue-700',
    quote: 'bg-purple-100 text-purple-700',
};
const TYPE_LABELS = { invoice: 'Facture', quote: 'Devis' };

const STATUS_STYLES = {
    draft: 'bg-gray-100 text-gray-700',
    issued: 'bg-blue-100 text-blue-700',
    paid: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
};
const STATUS_LABELS = { draft: 'Brouillon', issued: 'Émise', paid: 'Payée', cancelled: 'Annulée' };

function Badge({ value, styleMap, labelMap }) {
    return (
        <span
            className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${styleMap[value] ?? 'bg-gray-100 text-gray-600'}`}
        >
            {labelMap[value] ?? value}
        </span>
    );
}

export default function Index({ invoices, filters, types, statuses, can }) {
    const [search, setSearch] = useState(filters?.search ?? '');
    const isFirstRender = useRef(true);
    const filtersRef = useRef(filters);

    useEffect(() => {
        filtersRef.current = filters;
    });

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }
        const timer = setTimeout(() => {
            const f = filtersRef.current;
            navigate({
                search: search || undefined,
                type: f?.type,
                status: f?.status,
                date_from: f?.date_from,
                date_to: f?.date_to,
            });
        }, 300);
        return () => clearTimeout(timer);
    }, [search]);

    function navigate(params) {
        router.get(
            route('invoices.index'),
            {
                search: params.search || undefined,
                type: params.type || undefined,
                status: params.status || undefined,
                date_from: params.date_from || undefined,
                date_to: params.date_to || undefined,
            },
            { preserveState: true, replace: true },
        );
    }

    function setFilter(key, value) {
        navigate({
            search: search || undefined,
            type: filters?.type,
            status: filters?.status,
            date_from: filters?.date_from,
            date_to: filters?.date_to,
            [key]: value,
        });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Factures &amp; Devis
                    </h2>
                    {can.create && (
                        <div className="flex items-center gap-2">
                            <Link
                                href={route('invoices.create', { type: 'invoice' })}
                                className="inline-flex min-h-[44px] items-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                            >
                                + Nouvelle facture
                            </Link>
                            <Link
                                href={route('invoices.create', { type: 'quote' })}
                                className="inline-flex min-h-[44px] items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                + Nouveau devis
                            </Link>
                        </div>
                    )}
                </div>
            }
        >
            <Head title="Factures & Devis" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        {/* TOOLBAR */}
                        <div className="flex flex-wrap items-end gap-3 border-b p-4">
                            <TextInput
                                id="search"
                                name="search"
                                className="min-w-48 flex-1"
                                placeholder="Rechercher par N° ou client…"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />

                            <select
                                id="type"
                                name="type"
                                className="min-h-[44px] rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                value={filters?.type || ''}
                                onChange={(e) => setFilter('type', e.target.value)}
                            >
                                <option value="">Tous les types</option>
                                {types.map((t) => (
                                    <option key={t.value} value={t.value}>
                                        {t.label}
                                    </option>
                                ))}
                            </select>

                            <select
                                id="status"
                                name="status"
                                className="min-h-[44px] rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                value={filters?.status || ''}
                                onChange={(e) => setFilter('status', e.target.value)}
                            >
                                <option value="">Tous les statuts</option>
                                {statuses.map((s) => (
                                    <option key={s.value} value={s.value}>
                                        {s.label}
                                    </option>
                                ))}
                            </select>

                            <div className="flex items-center gap-1 text-sm text-gray-600">
                                <label htmlFor="date_from" className="whitespace-nowrap">
                                    Du
                                </label>
                                <input
                                    id="date_from"
                                    type="date"
                                    name="date_from"
                                    value={filters?.date_from || ''}
                                    onChange={(e) => setFilter('date_from', e.target.value)}
                                    className="min-h-[44px] rounded-md border border-gray-300 px-2 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                />
                                <label htmlFor="date_to" className="whitespace-nowrap">
                                    au
                                </label>
                                <input
                                    id="date_to"
                                    type="date"
                                    name="date_to"
                                    value={filters?.date_to || ''}
                                    onChange={(e) => setFilter('date_to', e.target.value)}
                                    className="min-h-[44px] rounded-md border border-gray-300 px-2 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                />
                            </div>

                            {(search ||
                                filters?.type ||
                                filters?.status ||
                                filters?.date_from ||
                                filters?.date_to) && (
                                <button
                                    type="button"
                                    onClick={() => {
                                        setSearch('');
                                        navigate({});
                                    }}
                                    className="min-h-[44px] rounded-md border px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"
                                >
                                    Réinitialiser
                                </button>
                            )}
                        </div>

                        {/* TABLE */}
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                                        N°
                                    </th>
                                    <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Type
                                    </th>
                                    <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Statut
                                    </th>
                                    <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Client
                                    </th>
                                    <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Date
                                    </th>
                                    <th className="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Total TTC
                                    </th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {invoices.data.map((inv) => (
                                    <tr key={inv.id} className="hover:bg-gray-50">
                                        <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-primary">
                                            <Link href={route('invoices.show', inv.id)}>
                                                {inv.number}
                                            </Link>
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm">
                                            <Badge
                                                value={inv.type}
                                                styleMap={TYPE_STYLES}
                                                labelMap={TYPE_LABELS}
                                            />
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm">
                                            <Badge
                                                value={inv.status}
                                                styleMap={STATUS_STYLES}
                                                labelMap={STATUS_LABELS}
                                            />
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                            {inv.customer ? (
                                                <Link
                                                    href={route('customers.show', inv.customer.id)}
                                                    className="hover:underline"
                                                >
                                                    {inv.customer.name}
                                                </Link>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {inv.issue_date}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-end text-sm font-medium text-gray-900">
                                            {Number(inv.total_ttc).toFixed(2)} DH
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-end text-sm">
                                            <Link
                                                href={route('invoices.show', inv.id)}
                                                className="inline-flex min-h-[44px] items-center text-primary hover:underline"
                                            >
                                                Voir
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                                {invoices.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-6 py-10 text-center text-sm text-gray-500"
                                        >
                                            Aucun document trouvé.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>

                        {/* PAGINATION */}
                        {invoices.links.length > 3 && (
                            <div className="flex items-center justify-between border-t border-gray-200 px-6 py-3">
                                {invoices.links.map((link, i) =>
                                    link.url ? (
                                        <Link
                                            key={i}
                                            href={link.url}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className={`inline-flex min-h-[44px] items-center px-3 text-sm ${link.active ? 'font-bold text-primary' : 'text-gray-500 hover:text-gray-700'}`}
                                        />
                                    ) : (
                                        <span
                                            key={i}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className="inline-flex min-h-[44px] items-center px-3 text-sm text-gray-300"
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
