import DangerButton from '@/Components/DangerButton';
import Dropdown from '@/Components/Dropdown';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

export default function Index({ customers, filters, can }) {
    const [search, setSearch] = useState(filters?.search ?? '');
    const [deletingCustomer, setDeletingCustomer] = useState(null);
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
                route('customers.index'),
                { search: search || undefined, sort: f?.sort, direction: f?.direction },
                { preserveState: true, replace: true },
            );
        }, 300);
        return () => clearTimeout(timer);
    }, [search]);

    function sortLink(field) {
        const isCurrent = filters?.sort === field;
        const direction = isCurrent && filters?.direction === 'asc' ? 'desc' : 'asc';
        return route('customers.index', { search: filters?.search ?? '', sort: field, direction });
    }

    function sortIcon(field) {
        if (filters?.sort !== field) return null;
        return filters?.direction === 'asc' ? ' ↑' : ' ↓';
    }

    function handleSortSelect(e) {
        router.get(
            route('customers.index'),
            { search: filters?.search ?? '', sort: e.target.value, direction: 'asc' },
            { preserveState: true, replace: true },
        );
    }

    function destroy() {
        setProcessing(true);
        router.delete(route('customers.destroy', deletingCustomer.id), {
            onFinish: () => {
                setProcessing(false);
                setDeletingCustomer(null);
            },
        });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Clients</h2>
                    <Link
                        href={route('customers.create')}
                        className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        + Nouveau client
                    </Link>
                </div>
            }
        >
            <Head title="Clients" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        {/* TOOLBAR */}
                        <div className="flex flex-wrap items-center gap-3 border-b p-4">
                            <TextInput
                                id="search"
                                name="search"
                                className="flex-1"
                                placeholder="Rechercher par nom, téléphone ou e-mail…"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                            <select
                                id="sort"
                                name="sort"
                                className="rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                value={filters?.sort || 'last_name'}
                                onChange={handleSortSelect}
                            >
                                <option value="last_name">Trier par : Nom</option>
                                <option value="first_name">Trier par : Prénom</option>
                                <option value="email">Trier par : E-mail</option>
                                <option value="last_visit" disabled>
                                    Dernière visite (bientôt)
                                </option>
                                <option value="total_spent" disabled>
                                    Total dépensé (bientôt)
                                </option>
                            </select>
                            {search && (
                                <button
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
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        <Link href={sortLink('last_name')}>
                                            Nom{sortIcon('last_name')}
                                        </Link>
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        <Link href={sortLink('first_name')}>
                                            Prénom{sortIcon('first_name')}
                                        </Link>
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        <Link href={sortLink('email')}>
                                            E-mail{sortIcon('email')}
                                        </Link>
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Téléphone
                                    </th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {customers.data.map((c) => (
                                    <tr key={c.id}>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                            {c.last_name}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {c.first_name}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {c.email ?? '—'}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {c.phone}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <Dropdown>
                                                <Dropdown.Trigger>
                                                    <button className="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-none">
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
                                                        href={route('customers.show', c.id)}
                                                    >
                                                        Voir
                                                    </Dropdown.Link>
                                                    <Dropdown.Link
                                                        href={route('customers.edit', c.id)}
                                                    >
                                                        Modifier
                                                    </Dropdown.Link>
                                                    {can.delete && (
                                                        <button
                                                            className="block w-full px-4 py-2 text-start text-sm leading-5 text-red-600 transition duration-150 ease-in-out hover:bg-red-50 focus:outline-none"
                                                            onClick={() => setDeletingCustomer(c)}
                                                        >
                                                            Supprimer
                                                        </button>
                                                    )}
                                                </Dropdown.Content>
                                            </Dropdown>
                                        </td>
                                    </tr>
                                ))}
                                {customers.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-6 py-8 text-center text-sm text-gray-500"
                                        >
                                            Aucun client trouvé.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>

                        {/* PAGINATION */}
                        {customers.links.length > 3 && (
                            <div className="flex items-center justify-between border-t border-gray-200 px-6 py-3">
                                {customers.links.map((link, i) =>
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
                show={deletingCustomer !== null}
                maxWidth="sm"
                onClose={() => setDeletingCustomer(null)}
            >
                <div className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">Supprimer ce client ?</h2>
                    <p className="mt-1 text-sm text-gray-600">
                        <span className="font-medium">
                            {deletingCustomer?.first_name} {deletingCustomer?.last_name}
                        </span>{' '}
                        sera définitivement supprimé. Cette action est irréversible.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setDeletingCustomer(null)}>
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
