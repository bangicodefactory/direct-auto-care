import DangerButton from '@/Components/DangerButton';
import Dropdown from '@/Components/Dropdown';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { MoreVertical } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

export default function Index({ products, filters, categories, can }) {
    const [search, setSearch] = useState(filters?.search ?? '');
    const [deletingProduct, setDeletingProduct] = useState(null);
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
                route('products.index'),
                {
                    search: search.trim() || undefined,
                    category: f?.category,
                    low_stock: f?.low_stock,
                },
                { preserveState: true, replace: true },
            );
        }, 300);
        return () => clearTimeout(timer);
    }, [search]);

    function filterCategory(value) {
        const f = filtersRef.current;
        router.get(
            route('products.index'),
            {
                category: value || undefined,
                low_stock: f?.low_stock,
                search: search.trim() || undefined,
            },
            { preserveState: true, replace: true },
        );
    }

    function toggleLowStock() {
        const f = filtersRef.current;
        router.get(
            route('products.index'),
            {
                category: f?.category,
                low_stock: f?.low_stock ? undefined : '1',
                search: search.trim() || undefined,
            },
            { preserveState: true, replace: true },
        );
    }

    function destroy() {
        setProcessing(true);
        router.delete(route('products.destroy', deletingProduct.id), {
            onFinish: () => {
                setProcessing(false);
                setDeletingProduct(null);
            },
        });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Produits</h2>
                    {can.create && (
                        <Link
                            href={route('products.create')}
                            className="inline-flex min-h-[44px] items-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            + Ajouter un produit
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Produits" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        {/* TOOLBAR */}
                        <div className="flex flex-wrap items-center gap-3 border-b p-4">
                            <TextInput
                                className="flex-1"
                                placeholder="Rechercher par nom ou référence…"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                            <select
                                className="min-h-[44px] rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                value={filters?.category ?? ''}
                                onChange={(e) => filterCategory(e.target.value)}
                            >
                                <option value="">Toutes catégories</option>
                                {categories.map((c) => (
                                    <option key={c.value} value={c.value}>
                                        {c.label}
                                    </option>
                                ))}
                            </select>
                            <button
                                type="button"
                                onClick={toggleLowStock}
                                className={`min-h-[44px] rounded-md border px-3 py-2 text-sm ${
                                    filters?.low_stock
                                        ? 'border-red-400 bg-red-50 text-red-700'
                                        : 'border-gray-300 text-gray-600 hover:bg-gray-50'
                                }`}
                            >
                                Stock faible seulement
                            </button>
                            {search && (
                                <button
                                    type="button"
                                    onClick={() => setSearch('')}
                                    className="min-h-[44px] rounded-md border px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                                >
                                    Effacer
                                </button>
                            )}
                        </div>

                        {/* TABLE */}
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Nom
                                    </th>
                                    <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Réf.
                                    </th>
                                    <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Catégorie
                                    </th>
                                    <th className="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Prix vente
                                    </th>
                                    <th className="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Qté
                                    </th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {products.data.map((p) => (
                                    <tr key={p.id}>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                            {p.name}
                                            {p.is_low_stock && (
                                                <span className="ms-2 inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">
                                                    Stock faible
                                                </span>
                                            )}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {p.sku ?? '—'}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {p.category}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-end text-sm text-gray-700">
                                            {Number(p.price_sell).toFixed(2)} DH
                                        </td>
                                        <td
                                            className={`whitespace-nowrap px-6 py-4 text-end text-sm font-semibold ${
                                                p.is_low_stock ? 'text-red-600' : 'text-gray-700'
                                            }`}
                                        >
                                            {p.quantity_on_hand} {p.unit}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-end text-sm font-medium">
                                            <Dropdown>
                                                <Dropdown.Trigger>
                                                    <button className="flex min-h-[44px] min-w-[44px] items-center justify-center rounded text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-none">
                                                        <MoreVertical className="h-5 w-5" />
                                                    </button>
                                                </Dropdown.Trigger>
                                                <Dropdown.Content align="right" width="48">
                                                    <Dropdown.Link
                                                        href={route('products.show', p.id)}
                                                    >
                                                        Voir
                                                    </Dropdown.Link>
                                                    {can.edit && (
                                                        <Dropdown.Link
                                                            href={route('products.edit', p.id)}
                                                        >
                                                            Modifier
                                                        </Dropdown.Link>
                                                    )}
                                                    {can.delete && (
                                                        <button
                                                            className="block w-full px-4 py-2 text-start text-sm leading-5 text-red-600 transition duration-150 ease-in-out hover:bg-red-50 focus:outline-none"
                                                            onClick={() => setDeletingProduct(p)}
                                                        >
                                                            Supprimer
                                                        </button>
                                                    )}
                                                </Dropdown.Content>
                                            </Dropdown>
                                        </td>
                                    </tr>
                                ))}
                                {products.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-6 py-8 text-center text-sm text-gray-500"
                                        >
                                            Aucun produit trouvé.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>

                        {/* PAGINATION */}
                        {products.links.length > 3 && (
                            <div className="flex items-center justify-between border-t border-gray-200 px-6 py-3">
                                {products.links.map((link, i) =>
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

            {/* DELETE MODAL */}
            <Modal
                show={deletingProduct !== null}
                maxWidth="sm"
                onClose={() => setDeletingProduct(null)}
            >
                <div className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">Supprimer ce produit ?</h2>
                    <p className="mt-1 text-sm text-gray-600">
                        <span className="font-medium">{deletingProduct?.name}</span> sera
                        définitivement supprimé. Cette action est irréversible.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setDeletingProduct(null)}>
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
