import Modal from '@/Components/Modal';
import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Paginated } from '@/types/pagination';
import { Head, Link, router } from '@inertiajs/react';
import { MoreVertical } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface ServiceRow {
    id: number;
    name: string;
    description: string | null;
    default_price_ht: string;
    is_active: boolean;
}

interface ServiceFilters {
    search?: string;
    active_only?: string;
}

interface IndexProps {
    services: Paginated<ServiceRow>;
    filters: ServiceFilters;
    can: { create: boolean; edit: boolean; delete: boolean };
}

export default function Index({ services, filters, can }: IndexProps) {
    const [search, setSearch] = useState(filters?.search ?? '');
    const [deletingService, setDeletingService] = useState<ServiceRow | null>(null);
    const [processing, setProcessing] = useState(false);
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
            router.get(
                route('services.index'),
                {
                    search: search.trim() || undefined,
                    active_only: f?.active_only,
                },
                { preserveState: true, replace: true },
            );
        }, 300);
        return () => clearTimeout(timer);
    }, [search]);

    function toggleActiveOnly() {
        const f = filtersRef.current;
        router.get(
            route('services.index'),
            {
                active_only: f?.active_only ? undefined : '1',
                search: search.trim() || undefined,
            },
            { preserveState: true, replace: true },
        );
    }

    function destroy() {
        setProcessing(true);
        router.delete(route('services.destroy', deletingService!.id), {
            onFinish: () => {
                setProcessing(false);
                setDeletingService(null);
            },
        });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-foreground">
                        Services
                    </h2>
                    {can.create && (
                        <Button asChild>
                            <Link href={route('services.create')}>+ Ajouter un service</Link>
                        </Button>
                    )}
                </div>
            }
        >
            <Head title="Services" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        {/* TOOLBAR */}
                        <div className="flex flex-wrap items-center gap-3 border-b border-border p-4">
                            <TextInput
                                className="flex-1"
                                placeholder="Rechercher par nom…"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                            <Button
                                type="button"
                                variant={filters?.active_only ? 'default' : 'outline'}
                                onClick={toggleActiveOnly}
                            >
                                Actifs seulement
                            </Button>
                            {search && (
                                <Button variant="outline" onClick={() => setSearch('')}>
                                    Effacer
                                </Button>
                            )}
                        </div>

                        {/* TABLE */}
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nom</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead className="text-end">Prix par défaut HT</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {services.data.map((s) => (
                                    <TableRow key={s.id}>
                                        <TableCell className="whitespace-nowrap font-medium text-foreground">
                                            {s.name}
                                        </TableCell>
                                        <TableCell className="max-w-xs truncate text-muted-foreground">
                                            {s.description ?? '—'}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-end tabular-nums text-foreground">
                                            {Number(s.default_price_ht).toFixed(2)} DH
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap">
                                            {s.is_active ? (
                                                <span className="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">
                                                    Actif
                                                </span>
                                            ) : (
                                                <span className="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800">
                                                    Inactif
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-end">
                                            {(can.edit || can.delete) && (
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <button className="flex min-h-[44px] min-w-[44px] items-center justify-center rounded text-muted-foreground hover:bg-accent hover:text-accent-foreground focus:outline-none">
                                                            <MoreVertical className="h-5 w-5" />
                                                        </button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent
                                                        align="end"
                                                        className="w-48"
                                                    >
                                                        {can.edit && (
                                                            <DropdownMenuItem asChild>
                                                                <Link
                                                                    href={route(
                                                                        'services.edit',
                                                                        s.id,
                                                                    )}
                                                                >
                                                                    Modifier
                                                                </Link>
                                                            </DropdownMenuItem>
                                                        )}
                                                        {can.delete && (
                                                            <DropdownMenuItem
                                                                className="text-destructive focus:text-destructive"
                                                                onClick={() =>
                                                                    setDeletingService(s)
                                                                }
                                                            >
                                                                Supprimer
                                                            </DropdownMenuItem>
                                                        )}
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {services.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            Aucun service trouvé.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>

                        {/* PAGINATION */}
                        {services.links.length > 3 && (
                            <div className="flex items-center justify-between border-t border-border px-6 py-3">
                                {services.links.map((link, i) =>
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

            <Modal
                show={deletingService !== null}
                maxWidth="sm"
                onClose={() => setDeletingService(null)}
            >
                <div className="p-6">
                    <h2 className="text-lg font-medium text-foreground">Supprimer ce service ?</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        <span className="font-medium">{deletingService?.name}</span> ne sera plus
                        proposé dans les devis, factures et ordres de service. Les documents
                        existants ne sont pas modifiés.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <Button variant="secondary" onClick={() => setDeletingService(null)}>
                            Annuler
                        </Button>
                        <Button variant="destructive" onClick={destroy} disabled={processing}>
                            Supprimer
                        </Button>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
