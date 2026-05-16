import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Show({ product, can }) {
    const [confirmDelete, setConfirmDelete] = useState(false);

    function handleDelete() {
        router.delete(route('products.destroy', product.id));
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    {product.name}
                </h2>
            }
        >
            <Head title={product.name} />

            <div className="py-8">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg bg-white shadow">
                        <dl className="divide-y divide-gray-100">
                            {[
                                ['Catégorie', product.category],
                                ['Référence', product.sku ?? '—'],
                                ['Unité', product.unit],
                                ["Prix d'achat", product.price_buy ? `${Number(product.price_buy).toFixed(2)} DH` : '—'],
                                ['Prix de vente', `${Number(product.price_sell).toFixed(2)} DH`],
                                ['Quantité en stock', `${product.quantity_on_hand} ${product.unit}`],
                                ["Seuil d'alerte", product.low_stock_threshold],
                            ].map(([label, value]) => (
                                <div key={label} className="grid grid-cols-3 gap-4 px-6 py-4">
                                    <dt className="text-sm font-medium text-gray-500">{label}</dt>
                                    <dd className="col-span-2 text-sm text-gray-900">{value}</dd>
                                </div>
                            ))}
                        </dl>
                    </div>

                    {product.is_low_stock && (
                        <p className="mt-3 rounded bg-red-50 px-4 py-2 text-sm font-medium text-red-700">
                            Stock faible — réapprovisionnement recommandé.
                        </p>
                    )}

                    <div className="mt-6 flex gap-3">
                        <Link href={route('products.index')} className="text-sm text-gray-600 hover:underline">
                            ← Retour
                        </Link>

                        {can.edit && (
                            <Link
                                href={route('products.edit', product.id)}
                                className="rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
                            >
                                Modifier
                            </Link>
                        )}

                        {can.delete && !confirmDelete && (
                            <button
                                type="button"
                                onClick={() => setConfirmDelete(true)}
                                className="rounded bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700"
                            >
                                Supprimer
                            </button>
                        )}

                        {confirmDelete && (
                            <div className="flex items-center gap-2">
                                <span className="text-sm text-gray-700">Confirmer la suppression ?</span>
                                <button type="button" onClick={handleDelete} className="rounded bg-red-600 px-3 py-1 text-sm text-white">
                                    Oui
                                </button>
                                <button type="button" onClick={() => setConfirmDelete(false)} className="rounded border px-3 py-1 text-sm">
                                    Annuler
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
