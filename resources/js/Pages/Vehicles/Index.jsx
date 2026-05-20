import DangerButton from '@/Components/DangerButton';
import Dropdown from '@/Components/Dropdown';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import { DropdownMenuItem } from '@/Components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { FUEL_LABELS } from '@/constants/fuelTypes';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { MoreVertical } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

function SortIcon({ column, currentSort, currentDirection }) {
    if (currentSort !== column) {
        return <span className="ms-1 text-muted-foreground/40">↕</span>;
    }
    return <span className="ms-1 text-primary">{currentDirection === 'asc' ? '↑' : '↓'}</span>;
}

function SortableHeader({ column, label, currentSort, currentDirection, onSort }) {
    return (
        <button
            type="button"
            onClick={() => onSort(column)}
            className="flex min-h-[44px] items-center text-xs font-medium uppercase tracking-wider text-muted-foreground hover:text-foreground"
        >
            {label}
            <SortIcon
                column={column}
                currentSort={currentSort}
                currentDirection={currentDirection}
            />
        </button>
    );
}

export default function Index({ vehicles, filters, can }) {
    const [search, setSearch] = useState(filters?.search ?? '');
    const [deletingVehicle, setDeletingVehicle] = useState(null);
    const [processing, setProcessing] = useState(false);
    const isFirstRender = useRef(true);

    const sort = filters?.sort ?? 'license_plate';
    const direction = filters?.direction ?? 'asc';

    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }
        const timer = setTimeout(() => {
            router.get(
                route('vehicles.index'),
                { search: search || undefined, sort, direction },
                { preserveState: true, replace: true },
            );
        }, 300);
        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    function handleSort(column) {
        const newDirection = sort === column && direction === 'asc' ? 'desc' : 'asc';
        router.get(
            route('vehicles.index'),
            { search: search || undefined, sort: column, direction: newDirection },
            { preserveState: true, replace: true },
        );
    }

    const sortProps = { currentSort: sort, currentDirection: direction, onSort: handleSort };

    function destroy() {
        setProcessing(true);
        router.delete(route('vehicles.destroy', deletingVehicle.id), {
            onFinish: () => {
                setProcessing(false);
                setDeletingVehicle(null);
            },
        });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-foreground">
                        Véhicules
                    </h2>
                    <Button asChild>
                        <Link href={route('vehicles.create')}>Nouveau véhicule</Link>
                    </Button>
                </div>
            }
        >
            <Head title="Véhicules" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        {/* TOOLBAR */}
                        <div className="flex flex-wrap items-center gap-3 border-b border-border p-4">
                            <TextInput
                                className="flex-1"
                                placeholder="Rechercher par plaque ou nom du client…"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
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
                                    <TableHead>
                                        <SortableHeader
                                            column="license_plate"
                                            label="Plaque"
                                            {...sortProps}
                                        />
                                    </TableHead>
                                    <TableHead>
                                        <SortableHeader
                                            column="make"
                                            label="Marque / Modèle"
                                            {...sortProps}
                                        />
                                    </TableHead>
                                    <TableHead>
                                        <SortableHeader
                                            column="year"
                                            label="Année"
                                            {...sortProps}
                                        />
                                    </TableHead>
                                    <TableHead>Carburant</TableHead>
                                    <TableHead>Client</TableHead>
                                    <TableHead />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {vehicles.data.map((v) => (
                                    <TableRow key={v.id}>
                                        <TableCell className="whitespace-nowrap font-mono font-medium text-foreground">
                                            {v.license_plate}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-foreground">
                                            {v.make} {v.model}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-muted-foreground">
                                            {v.year}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-muted-foreground">
                                            {FUEL_LABELS[v.fuel_type] ?? v.fuel_type}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap">
                                            <Link
                                                href={route('customers.show', v.customer.id)}
                                                className="text-primary hover:underline"
                                            >
                                                {v.customer.name}
                                            </Link>
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-end">
                                            <Dropdown>
                                                <Dropdown.Trigger>
                                                    <button
                                                        type="button"
                                                        className="flex min-h-[44px] min-w-[44px] items-center justify-center rounded text-muted-foreground hover:bg-accent hover:text-accent-foreground focus:outline-none"
                                                    >
                                                        <MoreVertical className="h-5 w-5" />
                                                    </button>
                                                </Dropdown.Trigger>
                                                <Dropdown.Content align="right" width="48">
                                                    <Dropdown.Link
                                                        href={route('vehicles.show', v.id)}
                                                    >
                                                        Voir
                                                    </Dropdown.Link>
                                                    <Dropdown.Link
                                                        href={route('vehicles.edit', v.id)}
                                                    >
                                                        Modifier
                                                    </Dropdown.Link>
                                                    {can.delete && (
                                                        <DropdownMenuItem
                                                            className="text-destructive focus:text-destructive"
                                                            onClick={() => setDeletingVehicle(v)}
                                                        >
                                                            Supprimer
                                                        </DropdownMenuItem>
                                                    )}
                                                </Dropdown.Content>
                                            </Dropdown>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {vehicles.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            Aucun véhicule trouvé.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>

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

            <Modal
                show={deletingVehicle !== null}
                maxWidth="sm"
                onClose={() => setDeletingVehicle(null)}
            >
                <div className="p-6">
                    <h2 className="text-lg font-medium text-foreground">Supprimer ce véhicule ?</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        <span className="font-medium">
                            {deletingVehicle?.make} {deletingVehicle?.model} —{' '}
                            {deletingVehicle?.license_plate}
                        </span>{' '}
                        sera supprimé. Cette action est irréversible.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setDeletingVehicle(null)}>
                            Annuler
                        </SecondaryButton>
                        <DangerButton onClick={destroy} disabled={processing}>
                            Supprimer définitivement
                        </DangerButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
