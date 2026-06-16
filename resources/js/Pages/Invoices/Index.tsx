import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { InvoiceStatus, InvoiceType } from '@/types/models';
import type { Paginated } from '@/types/pagination';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const TYPE_STYLES: Record<string, string> = {
    invoice: 'bg-blue-100 text-blue-700',
    quote: 'bg-purple-100 text-purple-700',
};
const TYPE_LABELS: Record<string, string> = { invoice: 'Facture', quote: 'Devis' };

const STATUS_STYLES: Record<string, string> = {
    draft: 'bg-muted text-foreground',
    issued: 'bg-blue-100 text-blue-700',
    paid: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
};
const STATUS_LABELS: Record<string, string> = {
    draft: 'Brouillon',
    issued: 'Émise',
    paid: 'Payée',
    cancelled: 'Annulée',
};

interface BadgeProps {
    value: string;
    styleMap: Record<string, string>;
    labelMap: Record<string, string>;
}

interface SelectOption {
    value: string;
    label: string;
}

interface IndexFilters {
    search?: string;
    type?: InvoiceType | '';
    status?: InvoiceStatus | '';
    date_from?: string;
    date_to?: string;
}

interface NavigateParams {
    search?: string;
    type?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
}

/** Row shape from InvoicesController@index (paginated `through()` projection). */
interface InvoiceRow {
    id: number;
    number: string;
    type: InvoiceType;
    status: InvoiceStatus;
    issue_date: string;
    total_ttc: string;
    customer: { id: number; name: string } | null;
}

interface IndexProps {
    invoices: Paginated<InvoiceRow>;
    filters: IndexFilters;
    types: SelectOption[];
    statuses: SelectOption[];
    can: { create: boolean };
}

function Badge({ value, styleMap, labelMap }: BadgeProps) {
    return (
        <span
            className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${styleMap[value] ?? 'bg-muted text-foreground'}`}
        >
            {labelMap[value] ?? value}
        </span>
    );
}

export default function Index({ invoices, filters, types, statuses, can }: IndexProps) {
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

    function navigate(params: NavigateParams) {
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

    function setFilter(key: keyof IndexFilters, value: string) {
        navigate({
            search: search || undefined,
            type: filters?.type,
            status: filters?.status,
            date_from: filters?.date_from,
            date_to: filters?.date_to,
            [key]: value,
        });
    }

    const hasFilters =
        search || filters?.type || filters?.status || filters?.date_from || filters?.date_to;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-foreground">
                        Factures &amp; Devis
                    </h2>
                    {can.create && (
                        <div className="flex items-center gap-2">
                            <Button asChild>
                                <Link href={route('invoices.create', { type: 'invoice' })}>
                                    + Nouvelle facture
                                </Link>
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={route('invoices.create', { type: 'quote' })}>
                                    + Nouveau devis
                                </Link>
                            </Button>
                        </div>
                    )}
                </div>
            }
        >
            <Head title="Factures & Devis" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        {/* TOOLBAR */}
                        <div className="flex flex-wrap items-end gap-3 border-b border-border p-4">
                            <TextInput
                                id="search"
                                name="search"
                                className="min-w-48 flex-1"
                                placeholder="Rechercher par N° ou client…"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />

                            <Select
                                value={filters?.type || '__all__'}
                                onValueChange={(v) => setFilter('type', v === '__all__' ? '' : v)}
                            >
                                <SelectTrigger className="min-h-[44px] w-44">
                                    <SelectValue placeholder="Tous les types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__all__">Tous les types</SelectItem>
                                    {types.map((t) => (
                                        <SelectItem key={t.value} value={t.value}>
                                            {t.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select
                                value={filters?.status || '__all__'}
                                onValueChange={(v) => setFilter('status', v === '__all__' ? '' : v)}
                            >
                                <SelectTrigger className="min-h-[44px] w-44">
                                    <SelectValue placeholder="Tous les statuts" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__all__">Tous les statuts</SelectItem>
                                    {statuses.map((s) => (
                                        <SelectItem key={s.value} value={s.value}>
                                            {s.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <div className="flex items-center gap-1 text-sm text-muted-foreground">
                                <label htmlFor="date_from" className="whitespace-nowrap">
                                    Du
                                </label>
                                <TextInput
                                    id="date_from"
                                    type="date"
                                    name="date_from"
                                    value={filters?.date_from || ''}
                                    onChange={(e) => setFilter('date_from', e.target.value)}
                                    className="min-h-[44px]"
                                />
                                <label htmlFor="date_to" className="whitespace-nowrap">
                                    au
                                </label>
                                <TextInput
                                    id="date_to"
                                    type="date"
                                    name="date_to"
                                    value={filters?.date_to || ''}
                                    onChange={(e) => setFilter('date_to', e.target.value)}
                                    className="min-h-[44px]"
                                />
                            </div>

                            {hasFilters && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        setSearch('');
                                        navigate({});
                                    }}
                                >
                                    Réinitialiser
                                </Button>
                            )}
                        </div>

                        {/* TABLE */}
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>
                                            <span dir="ltr">N°</span>
                                        </TableHead>
                                        <TableHead className="hidden sm:table-cell">Type</TableHead>
                                        <TableHead>Statut</TableHead>
                                        <TableHead>Client</TableHead>
                                        <TableHead className="hidden sm:table-cell">Date</TableHead>
                                        <TableHead className="text-end">Total TTC</TableHead>
                                        <TableHead />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {invoices.data.map((inv) => (
                                        <TableRow key={inv.id}>
                                            <TableCell className="whitespace-nowrap font-medium text-primary">
                                                <Link href={route('invoices.show', inv.id)}>
                                                    {inv.number}
                                                </Link>
                                            </TableCell>
                                            <TableCell className="hidden whitespace-nowrap sm:table-cell">
                                                <Badge
                                                    value={inv.type}
                                                    styleMap={TYPE_STYLES}
                                                    labelMap={TYPE_LABELS}
                                                />
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap">
                                                <Badge
                                                    value={inv.status}
                                                    styleMap={STATUS_STYLES}
                                                    labelMap={STATUS_LABELS}
                                                />
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap text-foreground">
                                                {inv.customer ? (
                                                    <Link
                                                        href={route(
                                                            'customers.show',
                                                            inv.customer.id,
                                                        )}
                                                        className="hover:underline"
                                                    >
                                                        {inv.customer.name}
                                                    </Link>
                                                ) : (
                                                    '—'
                                                )}
                                            </TableCell>
                                            <TableCell className="hidden whitespace-nowrap text-muted-foreground sm:table-cell">
                                                {inv.issue_date}
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap text-end font-medium text-foreground">
                                                {Number(inv.total_ttc).toFixed(2)} DH
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap text-end">
                                                <Link
                                                    href={route('invoices.show', inv.id)}
                                                    className="inline-flex min-h-[44px] items-center text-primary hover:underline"
                                                >
                                                    Voir
                                                </Link>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {invoices.data.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={7}
                                                className="py-10 text-center text-muted-foreground"
                                            >
                                                Aucun document trouvé.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {/* PAGINATION */}
                        {invoices.links.length > 3 && (
                            <div className="flex items-center justify-between border-t border-border px-6 py-3">
                                {invoices.links.map((link, i) =>
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
