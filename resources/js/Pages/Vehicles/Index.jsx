import DangerButton from '@/Components/DangerButton';
import Dropdown from '@/Components/Dropdown';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { FUEL_LABELS } from '@/constants/fuelTypes';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

function SortIcon({ column, currentSort, currentDirection }) {
    if (currentSort !== column) {
        return <span className="ml-1 text-gray-300">↕</span>;
    }
    return (
        <span className="ml-1 text-primary">
            {currentDirection === 'asc' ? '↑' : '↓'}
        </span>
    );
}

function SortableHeader({ column, label, currentSort, currentDirection, onSort }) {
    return (
        <button
            type="button"
            onClick={() => onSort(column)}
            className="flex items-center text-xs font-medium uppercase tracking-wider text-gray-500 hover:text-gray-700"
        >
            {label}
            <SortIcon column={column} currentSort={currentSort} currentDirection={currentDirection} />
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
        // sort/direction changes go through handleSort directly; adding them here
        // would trigger a spurious debounced navigation after every sort click.
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
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Véhicules</h2>
                    <Link
                        href={route('vehicles.create')}
                        className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        Nouveau véhicule
                    </Link>
                </div>
            }
        >
            <Head title="Véhicules" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        {/* TOOLBAR */}
                        <div className="flex flex-wrap items-center gap-3 border-b p-4">
                            <TextInput
                                className="flex-1"
                                placeholder="Rechercher par plaque ou nom du client…"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                            {search && (
                                <button
                                    type="button"
                                    onClick={() => setSearch('')}
                                    className="rounded-md border px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                                >
                                    Effacer
                                </button>
                            )}
                        </div>

                        {/* TABLE */}
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left">
                                        <SortableHeader column="license_plate" label="Plaque" {...sortProps} />
                                    </th>
                                    <th className="px-6 py-3 text-left">
                                        <SortableHeader column="make" label="Marque / Modèle" {...sortProps} />
                                    </th>
                                    <th className="px-6 py-3 text-left">
                                        <SortableHeader column="year" label="Année" {...sortProps} />
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Carburant
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Client
                                    </th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {vehicles.data.map((v) => (
                                    <tr key={v.id}>
                                        <td className="whitespace-nowrap px-6 py-4 font-mono text-sm font-medium text-gray-900">
                                            {v.license_plate}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                            {v.make} {v.model}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {v.year}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {FUEL_LABELS[v.fuel_type] ?? v.fuel_type}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            <Link
                                                href={route('customers.show', v.customer.id)}
                                                className="text-primary hover:underline"
                                            >
                                                {v.customer.name}
                                            </Link>
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <Dropdown>
                                                <Dropdown.Trigger>
                                                    <button
                                                        type="button"
                                                        className="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-none"
                                                    >
                                                        <svg
                                                            className="h-5 w-5"
                                                            fill="currentColor"
                                                            viewBox="0 0 20 20"
                                                        >
                                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                                        </svg>
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
                                                        <button
                                                            type="button"
                                                            className="block w-full px-4 py-2 text-start text-sm leading-5 text-red-600 transition duration-150 ease-in-out hover:bg-red-50 focus:outline-none"
                                                            onClick={() => setDeletingVehicle(v)}
                                                        >
                                                            Supprimer
                                                        </button>
                                                    )}
                                                </Dropdown.Content>
                                            </Dropdown>
                                        </td>
                                    </tr>
                                ))}
                                {vehicles.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-6 py-8 text-center text-sm text-gray-500"
                                        >
                                            Aucun véhicule trouvé.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>

                        {/* PAGINATION */}
                        {vehicles.links.length > 3 && (
                            <div className="flex items-center justify-between border-t border-gray-200 px-6 py-3">
                                {vehicles.links.map((link, i) =>
                                    link.url ? (
                                        <Link
                                            key={i}
                                            href={link.url}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className={`px-3 py-1 text-sm ${link.active ? 'font-bold text-primary' : 'text-gray-500 hover:text-gray-700'}`}
                                        />
                                    ) : (
                                        <span
                                            key={i}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className="px-3 py-1 text-sm text-gray-300"
                                        />
                                    ),
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* DELETE MODAL */}
            <Modal
                show={deletingVehicle !== null}
                maxWidth="sm"
                onClose={() => setDeletingVehicle(null)}
            >
                <div className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">Supprimer ce véhicule ?</h2>
                    <p className="mt-1 text-sm text-gray-600">
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
