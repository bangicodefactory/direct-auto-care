import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useMemo } from 'react';

const today = new Date().toISOString().slice(0, 10);

let _nextKey = 0;
function newItem() {
    return { _key: _nextKey++, product_id: '', description: '', quantity: 1, unit_price_ht: '' };
}

export default function Create({ customers, products, vehicles, types, defaultType }) {
    const { data, setData, post, processing, errors } = useForm({
        type: defaultType,
        customer_id: '',
        vehicle_id: '',
        issue_date: today,
        due_date: '',
        discount: '',
        tva_rate: '20',
        notes: '',
        items: [newItem()],
    });

    const filteredVehicles = useMemo(
        () => vehicles.filter((v) => String(v.customer_id) === String(data.customer_id)),
        [vehicles, data.customer_id],
    );

    const subtotalHt = data.items.reduce((sum, it) => {
        const qty = parseFloat(it.quantity) || 0;
        const price = parseFloat(it.unit_price_ht) || 0;
        return sum + qty * price;
    }, 0);

    const discount = parseFloat(data.discount) || 0;
    const tvaRate = parseFloat(data.tva_rate) || 0;
    const tvaAmount = Math.round((((subtotalHt - discount) * tvaRate) / 100) * 100) / 100;
    const totalTtc = subtotalHt - discount + tvaAmount;

    function handleCustomerChange(id) {
        setData((prev) => ({ ...prev, customer_id: id, vehicle_id: '' }));
    }

    function handleProductChange(index, productId) {
        const product = products.find((p) => String(p.id) === String(productId));
        setData(
            'items',
            data.items.map((it, i) =>
                i === index
                    ? {
                          ...it,
                          product_id: productId,
                          description: product ? product.name : it.description,
                          unit_price_ht: product ? String(product.price_sell) : it.unit_price_ht,
                      }
                    : it,
            ),
        );
    }

    function updateItem(index, field, value) {
        setData(
            'items',
            data.items.map((it, i) => (i === index ? { ...it, [field]: value } : it)),
        );
    }

    function addItem() {
        setData('items', [...data.items, newItem()]);
    }

    function removeItem(index) {
        setData(
            'items',
            data.items.filter((_, i) => i !== index),
        );
    }

    function submit(e) {
        e.preventDefault();
        post(route('invoices.store'));
    }

    const isQuote = data.type === 'quote';

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        {isQuote ? 'Nouveau devis' : 'Nouvelle facture'}
                    </h2>
                    <Link
                        href={route('invoices.index')}
                        className="text-sm text-gray-500 hover:text-gray-700"
                    >
                        ← Retour
                    </Link>
                </div>
            }
        >
            <Head title={isQuote ? 'Nouveau devis' : 'Nouvelle facture'} />

            <div className="py-8">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="space-y-6">
                        {/* ── TYPE ── */}
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <InputLabel className="mb-2">Type de document</InputLabel>
                                <div className="flex gap-2">
                                    {types.map((t) => (
                                        <button
                                            key={t.value}
                                            type="button"
                                            onClick={() => setData('type', t.value)}
                                            className={`rounded-md px-5 py-2 text-sm font-medium transition-colors ${
                                                data.type === t.value
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                                            }`}
                                        >
                                            {t.label}
                                        </button>
                                    ))}
                                </div>
                                <InputError message={errors.type} className="mt-1" />
                            </div>
                        </div>

                        {/* ── CLIENT + VÉHICULE + DATES ── */}
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="mb-4 text-base font-semibold text-gray-700">
                                    Informations générales
                                </h3>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    {/* Customer */}
                                    <div>
                                        <InputLabel htmlFor="customer_id" value="Client *" />
                                        <select
                                            id="customer_id"
                                            name="customer_id"
                                            className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                            value={data.customer_id}
                                            onChange={(e) => handleCustomerChange(e.target.value)}
                                        >
                                            <option value="">— Sélectionner un client —</option>
                                            {customers.map((c) => (
                                                <option key={c.id} value={c.id}>
                                                    {c.first_name} {c.last_name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.customer_id} className="mt-1" />
                                    </div>

                                    {/* Vehicle */}
                                    <div>
                                        <InputLabel htmlFor="vehicle_id" value="Véhicule" />
                                        <select
                                            id="vehicle_id"
                                            name="vehicle_id"
                                            className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:bg-gray-50 disabled:text-gray-400"
                                            value={data.vehicle_id}
                                            onChange={(e) => setData('vehicle_id', e.target.value)}
                                            disabled={!data.customer_id}
                                        >
                                            <option value="">— Aucun —</option>
                                            {filteredVehicles.map((v) => (
                                                <option key={v.id} value={v.id}>
                                                    {v.license_plate} — {v.make} {v.model}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.vehicle_id} className="mt-1" />
                                    </div>

                                    {/* Issue date */}
                                    <div>
                                        <InputLabel
                                            htmlFor="issue_date"
                                            value="Date d'émission *"
                                        />
                                        <TextInput
                                            id="issue_date"
                                            name="issue_date"
                                            type="date"
                                            className="mt-1 block w-full"
                                            value={data.issue_date}
                                            onChange={(e) => setData('issue_date', e.target.value)}
                                        />
                                        <InputError message={errors.issue_date} className="mt-1" />
                                    </div>

                                    {/* Due date */}
                                    <div>
                                        <InputLabel htmlFor="due_date" value="Échéance" />
                                        <TextInput
                                            id="due_date"
                                            name="due_date"
                                            type="date"
                                            className="mt-1 block w-full"
                                            value={data.due_date}
                                            onChange={(e) => setData('due_date', e.target.value)}
                                        />
                                        <InputError message={errors.due_date} className="mt-1" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* ── LINE ITEMS ── */}
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="mb-4 text-base font-semibold text-gray-700">
                                    Lignes de document
                                </h3>

                                {errors.items && (
                                    <div className="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                        {errors.items}
                                    </div>
                                )}

                                <div className="overflow-x-auto">
                                    <table className="min-w-full text-sm">
                                        <thead>
                                            <tr className="border-b text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                                                <th className="pb-2 pe-3">Produit (optionnel)</th>
                                                <th className="pb-2 pe-3">Description *</th>
                                                <th className="pb-2 pe-3">Qté *</th>
                                                <th className="pb-2 pe-3">P.U. HT *</th>
                                                <th className="pb-2 pe-3 text-end">Total HT</th>
                                                <th className="pb-2" />
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-100">
                                            {data.items.map((item, index) => {
                                                const lineTotal =
                                                    (parseFloat(item.quantity) || 0) *
                                                    (parseFloat(item.unit_price_ht) || 0);
                                                return (
                                                    <tr key={item._key}>
                                                        <td className="py-2 pe-3">
                                                            <select
                                                                className="w-40 rounded-md border border-gray-300 px-2 py-1.5 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                                                value={item.product_id}
                                                                onChange={(e) =>
                                                                    handleProductChange(
                                                                        index,
                                                                        e.target.value,
                                                                    )
                                                                }
                                                            >
                                                                <option value="">— Aucun —</option>
                                                                {products.map((p) => (
                                                                    <option key={p.id} value={p.id}>
                                                                        {p.name}
                                                                    </option>
                                                                ))}
                                                            </select>
                                                        </td>
                                                        <td className="py-2 pe-3">
                                                            <TextInput
                                                                className="w-48"
                                                                value={item.description}
                                                                onChange={(e) =>
                                                                    updateItem(
                                                                        index,
                                                                        'description',
                                                                        e.target.value,
                                                                    )
                                                                }
                                                                placeholder="Description"
                                                            />
                                                            <InputError
                                                                message={
                                                                    errors[
                                                                        `items.${index}.description`
                                                                    ]
                                                                }
                                                                className="mt-0.5"
                                                            />
                                                        </td>
                                                        <td className="py-2 pe-3">
                                                            <TextInput
                                                                type="number"
                                                                className="w-20"
                                                                min="0.01"
                                                                step="0.01"
                                                                value={item.quantity}
                                                                onChange={(e) =>
                                                                    updateItem(
                                                                        index,
                                                                        'quantity',
                                                                        e.target.value,
                                                                    )
                                                                }
                                                            />
                                                            <InputError
                                                                message={
                                                                    errors[
                                                                        `items.${index}.quantity`
                                                                    ]
                                                                }
                                                                className="mt-0.5"
                                                            />
                                                        </td>
                                                        <td className="py-2 pe-3">
                                                            <TextInput
                                                                type="number"
                                                                className="w-28"
                                                                min="0"
                                                                step="0.01"
                                                                value={item.unit_price_ht}
                                                                onChange={(e) =>
                                                                    updateItem(
                                                                        index,
                                                                        'unit_price_ht',
                                                                        e.target.value,
                                                                    )
                                                                }
                                                            />
                                                            <InputError
                                                                message={
                                                                    errors[
                                                                        `items.${index}.unit_price_ht`
                                                                    ]
                                                                }
                                                                className="mt-0.5"
                                                            />
                                                        </td>
                                                        <td className="py-2 pe-3 text-end font-medium text-gray-900">
                                                            {lineTotal.toFixed(2)} DH
                                                        </td>
                                                        <td className="py-2">
                                                            {data.items.length > 1 && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() =>
                                                                        removeItem(index)
                                                                    }
                                                                    className="text-red-500 hover:text-red-700"
                                                                    title="Supprimer la ligne"
                                                                >
                                                                    <X className="h-4 w-4" />
                                                                </button>
                                                            )}
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>

                                <button
                                    type="button"
                                    onClick={addItem}
                                    className="mt-3 text-sm font-medium text-primary hover:underline"
                                >
                                    + Ajouter une ligne
                                </button>
                            </div>
                        </div>

                        {/* ── TOTALS + NOTES ── */}
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex flex-col gap-6 sm:flex-row">
                                    {/* Notes */}
                                    <div className="flex-1">
                                        <InputLabel htmlFor="notes" value="Notes" />
                                        <textarea
                                            id="notes"
                                            name="notes"
                                            rows={4}
                                            className="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                            value={data.notes}
                                            onChange={(e) => setData('notes', e.target.value)}
                                            placeholder="Remarques, conditions de paiement…"
                                        />
                                        <InputError message={errors.notes} className="mt-1" />
                                    </div>

                                    {/* Totals */}
                                    <div className="w-full sm:w-72">
                                        <div className="space-y-3">
                                            {/* Discount */}
                                            <div className="flex items-center justify-between gap-3">
                                                <InputLabel
                                                    htmlFor="discount"
                                                    value="Remise (DH)"
                                                    className="whitespace-nowrap"
                                                />
                                                <TextInput
                                                    id="discount"
                                                    name="discount"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    className="w-32 text-right"
                                                    value={data.discount}
                                                    onChange={(e) =>
                                                        setData('discount', e.target.value)
                                                    }
                                                    placeholder="0.00"
                                                />
                                            </div>
                                            <InputError
                                                message={errors.discount}
                                                className="-mt-2"
                                            />

                                            {/* TVA rate */}
                                            <div className="flex items-center justify-between gap-3">
                                                <InputLabel
                                                    htmlFor="tva_rate"
                                                    value="TVA (%)"
                                                    className="whitespace-nowrap"
                                                />
                                                <TextInput
                                                    id="tva_rate"
                                                    name="tva_rate"
                                                    type="number"
                                                    min="0"
                                                    max="100"
                                                    step="0.01"
                                                    className="w-32 text-right"
                                                    value={data.tva_rate}
                                                    onChange={(e) =>
                                                        setData('tva_rate', e.target.value)
                                                    }
                                                />
                                            </div>
                                            <InputError
                                                message={errors.tva_rate}
                                                className="-mt-2"
                                            />

                                            {/* Computed totals */}
                                            <div className="border-t pt-3 text-sm">
                                                <div className="flex justify-between text-gray-600">
                                                    <span>Sous-total HT</span>
                                                    <span>{subtotalHt.toFixed(2)} DH</span>
                                                </div>
                                                {discount > 0 && (
                                                    <div className="flex justify-between text-gray-600">
                                                        <span>Remise</span>
                                                        <span>− {discount.toFixed(2)} DH</span>
                                                    </div>
                                                )}
                                                <div className="flex justify-between text-gray-600">
                                                    <span>TVA ({tvaRate} %)</span>
                                                    <span>{tvaAmount.toFixed(2)} DH</span>
                                                </div>
                                                <div
                                                    className={`mt-2 flex justify-between border-t pt-2 text-base font-bold ${totalTtc < 0 ? 'text-red-600' : 'text-gray-900'}`}
                                                >
                                                    <span>Total TTC</span>
                                                    <span>{totalTtc.toFixed(2)} DH</span>
                                                </div>
                                                {totalTtc < 0 && (
                                                    <p className="mt-1 text-xs text-red-600">
                                                        La remise dépasse le sous-total.
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* ── ACTIONS ── */}
                        <div className="flex items-center justify-end gap-3">
                            <Link
                                href={route('invoices.index')}
                                className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Annuler
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                            >
                                {processing
                                    ? 'Enregistrement…'
                                    : isQuote
                                      ? 'Créer le devis'
                                      : 'Créer la facture'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
