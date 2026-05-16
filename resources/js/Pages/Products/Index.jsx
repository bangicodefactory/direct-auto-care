import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ products, filters, categories, can }) {
    function filterCategory(value) {
        router.get(route('products.index'), { ...filters, category: value || undefined }, { preserveState: true, replace: true });
    }

    function toggleLowStock() {
        router.get(route('products.index'), { ...filters, low_stock: filters.low_stock ? undefined : '1' }, { preserveState: true, replace: true });
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Produits</h2>}>
            <Head title="Produits" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-4 flex flex-wrap items-center gap-3">
                        <select
                            className="rounded border-gray-300 text-sm"
                            value={filters.category ?? ''}
                            onChange={(e) => filterCategory(e.target.value)}
                        >
                            <option value="">Toutes catégories</option>
                            {categories.map((c) => (
                                <option key={c.value} value={c.value}>
                                    {c.name}
                                </option>
                            ))}
                        </select>

                        <button
                            type="button"
                            onClick={toggleLowStock}
                            className={`rounded border px-3 py-1 text-sm ${filters.low_stock ? 'border-red-500 bg-red-50 text-red-700' : 'border-gray-300 text-gray-600'}`}
                        >
                            Stock faible seulement
                        </button>

                        {can.create && (
                            <Link href={route('products.create')} className="ml-auto rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700">
                                Ajouter un produit
                            </Link>
                        )}
                    </div>

                    <div className="overflow-hidden rounded-lg bg-white shadow">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Nom</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Réf.</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Catégorie</th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Prix vente</th>
                                    <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Qté</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 bg-white">
                                {products.data.map((p) => (
                                    <tr key={p.id} className={p.is_low_stock ? 'bg-red-50' : ''}>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">{p.name}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{p.sku ?? '—'}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{p.category}</td>
                                        <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{Number(p.price_sell).toFixed(2)} DH</td>
                                        <td className={`whitespace-nowrap px-4 py-3 text-right text-sm font-semibold ${p.is_low_stock ? 'text-red-600' : 'text-gray-700'}`}>
                                            {p.quantity_on_hand} {p.unit}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-right text-sm">
                                            <Link href={route('products.show', p.id)} className="text-indigo-600 hover:underline">
                                                Voir
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="mt-4 flex justify-between text-sm text-gray-600">
                        {products.prev_page_url && (
                            <Link href={products.prev_page_url} className="hover:underline">← Précédent</Link>
                        )}
                        <span className="ml-auto">
                            {products.from}–{products.to} sur {products.total}
                        </span>
                        {products.next_page_url && (
                            <Link href={products.next_page_url} className="ml-4 hover:underline">Suivant →</Link>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
