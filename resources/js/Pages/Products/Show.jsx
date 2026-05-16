import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const TYPE_LABELS = { in: 'Entrée', out: 'Sortie', adjustment: 'Ajustement' };

export default function Show({ product, movements, can }) {
    const [confirmDelete, setConfirmDelete] = useState(false);
    const [adjusting, setAdjusting] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        quantity: '',
        reason: '',
    });

    function handleDelete() {
        router.delete(route('products.destroy', product.id));
    }

    function submitAdjustment(e) {
        e.preventDefault();
        post(route('products.adjust-stock', product.id), {
            onSuccess: () => {
                reset();
                setAdjusting(false);
            },
        });
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

            <div className="py-12">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    {/* Product info */}
                    <div className="overflow-hidden rounded-lg bg-white shadow">
                        <dl className="divide-y divide-gray-100">
                            {[
                                ['Catégorie', product.category],
                                ['Référence', product.sku ?? '—'],
                                ['Unité', product.unit],
                                [
                                    "Prix d'achat",
                                    product.price_buy
                                        ? `${Number(product.price_buy).toFixed(2)} DH`
                                        : '—',
                                ],
                                ['Prix de vente', `${Number(product.price_sell).toFixed(2)} DH`],
                                [
                                    'Quantité en stock',
                                    `${product.quantity_on_hand} ${product.unit}`,
                                ],
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

                    {/* Actions */}
                    <div className="mt-6 flex flex-wrap gap-3">
                        <Link
                            href={route('products.index')}
                            className="text-sm text-gray-600 hover:underline"
                        >
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

                        {can.adjust && (
                            <button
                                type="button"
                                onClick={() => setAdjusting(true)}
                                className="rounded bg-emerald-600 px-4 py-2 text-sm text-white hover:bg-emerald-700"
                            >
                                Ajuster le stock
                            </button>
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
                                <span className="text-sm text-gray-700">
                                    Confirmer la suppression ?
                                </span>
                                <button
                                    type="button"
                                    onClick={handleDelete}
                                    className="rounded bg-red-600 px-3 py-1 text-sm text-white"
                                >
                                    Oui
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setConfirmDelete(false)}
                                    className="rounded border px-3 py-1 text-sm"
                                >
                                    Annuler
                                </button>
                            </div>
                        )}
                    </div>

                    {/* Stock movements log */}
                    <div className="mt-10">
                        <h3 className="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-500">
                            Derniers mouvements de stock
                        </h3>
                        <div className="overflow-hidden rounded-lg bg-white shadow">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Date
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Type
                                        </th>
                                        <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Quantité
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Motif
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Par
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 bg-white">
                                    {movements.map((m) => (
                                        <tr key={m.id}>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                                {m.created_at}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                                {TYPE_LABELS[m.type] ?? m.type}
                                            </td>
                                            <td
                                                className={`whitespace-nowrap px-4 py-3 text-right text-sm font-semibold ${
                                                    m.quantity > 0
                                                        ? 'text-emerald-600'
                                                        : 'text-red-600'
                                                }`}
                                            >
                                                {m.quantity > 0 ? `+${m.quantity}` : m.quantity}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-500">
                                                {m.reason ?? '—'}
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                                {m.user ?? '—'}
                                            </td>
                                        </tr>
                                    ))}
                                    {movements.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={5}
                                                className="px-4 py-6 text-center text-sm text-gray-400"
                                            >
                                                Aucun mouvement de stock enregistré.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {/* Adjust stock modal */}
            <Modal show={adjusting} maxWidth="sm" onClose={() => setAdjusting(false)}>
                <form onSubmit={submitAdjustment} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">Ajuster le stock</h2>
                    <p className="mt-1 text-sm text-gray-500">
                        Stock actuel :{' '}
                        <span className="font-semibold">
                            {product.quantity_on_hand} {product.unit}
                        </span>
                        . Entrez une valeur positive pour ajouter, négative pour retirer.
                    </p>

                    <div className="mt-4">
                        <label className="block text-sm font-medium text-gray-700">
                            Quantité <span className="text-red-500">*</span>
                        </label>
                        <TextInput
                            type="number"
                            className="mt-1 w-full"
                            value={data.quantity}
                            onChange={(e) => setData('quantity', e.target.value)}
                            autoFocus
                        />
                        {errors.quantity && (
                            <p className="mt-1 text-sm text-red-600">{errors.quantity}</p>
                        )}
                    </div>

                    <div className="mt-4">
                        <label className="block text-sm font-medium text-gray-700">
                            Motif (optionnel)
                        </label>
                        <TextInput
                            type="text"
                            className="mt-1 w-full"
                            placeholder="Ex. : Inventaire physique, casse…"
                            value={data.reason}
                            onChange={(e) => setData('reason', e.target.value)}
                        />
                        {errors.reason && (
                            <p className="mt-1 text-sm text-red-600">{errors.reason}</p>
                        )}
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={() => setAdjusting(false)}>
                            Annuler
                        </SecondaryButton>
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                        >
                            Enregistrer
                        </button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
