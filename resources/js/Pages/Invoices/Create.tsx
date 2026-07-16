import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Customer, InvoiceType, Product, Vehicle } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useMemo } from 'react';

const today = new Date().toISOString().slice(0, 10);

interface LineItem {
    _key: number;
    product_id: string | number;
    vehicle_id: string | number;
    description: string;
    quantity: string | number;
    unit_price_ht: string;
}

interface SelectOption {
    value: string;
    label: string;
}

interface CreateForm {
    type: InvoiceType | string;
    customer_id: string | number;
    vehicle_id: string | number;
    issue_date: string;
    due_date: string;
    discount: string;
    tva_rate: string;
    notes: string;
    items: LineItem[];
}

interface CreateProps {
    customers: Pick<Customer, 'id' | 'display_name'>[];
    products: Pick<Product, 'id' | 'name' | 'unit' | 'price_sell'>[];
    vehicles: Pick<Vehicle, 'id' | 'customer_id' | 'license_plate' | 'make' | 'model'>[];
    types: SelectOption[];
    defaultType: InvoiceType;
}

let _nextKey = 0;
function newItem(): LineItem {
    return {
        _key: _nextKey++,
        product_id: '',
        vehicle_id: '',
        description: '',
        quantity: 1,
        unit_price_ht: '',
    };
}

export default function Create({ customers, products, vehicles, types, defaultType }: CreateProps) {
    const { data, setData, post, processing, errors } = useForm<CreateForm>({
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

    // With 2+ vehicles the client can spread services across them on one invoice, so
    // attribution moves to a per-line Véhicule column and the single header field is hidden.
    const isMultiVehicle = filteredVehicles.length >= 2;

    const subtotalHt = data.items.reduce((sum: number, it: LineItem) => {
        const qty = parseFloat(it.quantity as string) || 0;
        const price = parseFloat(it.unit_price_ht) || 0;
        return sum + qty * price;
    }, 0);

    const discount = parseFloat(data.discount) || 0;
    const tvaRate = parseFloat(data.tva_rate) || 0;
    const tvaAmount = Math.round((((subtotalHt - discount) * tvaRate) / 100) * 100) / 100;
    const totalTtc = subtotalHt - discount + tvaAmount;

    function handleCustomerChange(id: string) {
        // Reset both the header vehicle and any per-line vehicles — they belonged to the
        // previous client's fleet and are no longer valid for the new one.
        setData((prev) => ({
            ...prev,
            customer_id: id,
            vehicle_id: '',
            items: prev.items.map((it) => ({ ...it, vehicle_id: '' })),
        }));
    }

    function handleProductChange(index: number, productId: string) {
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

    function updateItem(index: number, field: keyof LineItem, value: string) {
        setData(
            'items',
            data.items.map((it, i) => (i === index ? { ...it, [field]: value } : it)),
        );
    }

    function addItem() {
        setData('items', [...data.items, newItem()]);
    }

    function removeItem(index: number) {
        setData(
            'items',
            data.items.filter((_, i) => i !== index),
        );
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(route('invoices.store'));
    }

    const isQuote = data.type === 'quote';

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-foreground">
                        {isQuote ? 'Nouveau devis' : 'Nouvelle facture'}
                    </h2>
                    <Link
                        href={route('invoices.index')}
                        className="text-sm text-muted-foreground hover:text-foreground"
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
                        {/* TYPE */}
                        <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
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
                                                    : 'border border-border bg-background text-foreground hover:bg-accent'
                                            }`}
                                        >
                                            {t.label}
                                        </button>
                                    ))}
                                </div>
                                <InputError message={errors.type} className="mt-1" />
                            </div>
                        </div>

                        {/* CLIENT + VÉHICULE + DATES */}
                        <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                            <div className="p-6">
                                <h3 className="mb-4 text-base font-semibold text-foreground">
                                    Informations générales
                                </h3>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel htmlFor="customer_id" value="Client *" />
                                        <Select
                                            value={
                                                data.customer_id
                                                    ? String(data.customer_id)
                                                    : '__none__'
                                            }
                                            onValueChange={(v) =>
                                                handleCustomerChange(v === '__none__' ? '' : v)
                                            }
                                        >
                                            <SelectTrigger
                                                id="customer_id"
                                                className="mt-1 min-h-[44px] w-full"
                                            >
                                                <SelectValue placeholder="— Sélectionner un client —" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="__none__">
                                                    — Sélectionner un client —
                                                </SelectItem>
                                                {customers.map((c) => (
                                                    <SelectItem key={c.id} value={String(c.id)}>
                                                        {c.display_name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.customer_id} className="mt-1" />
                                    </div>

                                    {isMultiVehicle ? (
                                        <div>
                                            <InputLabel value="Véhicule" />
                                            <p className="mt-2 text-sm text-muted-foreground">
                                                Ce client a plusieurs véhicules — précisez le
                                                véhicule sur chaque ligne ci-dessous.
                                            </p>
                                        </div>
                                    ) : (
                                        <div>
                                            <InputLabel htmlFor="vehicle_id" value="Véhicule" />
                                            <Select
                                                value={
                                                    data.vehicle_id
                                                        ? String(data.vehicle_id)
                                                        : '__none__'
                                                }
                                                onValueChange={(v) =>
                                                    setData('vehicle_id', v === '__none__' ? '' : v)
                                                }
                                                disabled={!data.customer_id}
                                            >
                                                <SelectTrigger
                                                    id="vehicle_id"
                                                    className="mt-1 min-h-[44px] w-full"
                                                >
                                                    <SelectValue placeholder="— Aucun —" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="__none__">
                                                        — Aucun —
                                                    </SelectItem>
                                                    {filteredVehicles.map((v) => (
                                                        <SelectItem key={v.id} value={String(v.id)}>
                                                            {v.license_plate} — {v.make} {v.model}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={errors.vehicle_id}
                                                className="mt-1"
                                            />
                                        </div>
                                    )}

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

                        {/* LINE ITEMS */}
                        <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                            <div className="p-6">
                                <h3 className="mb-4 text-base font-semibold text-foreground">
                                    Lignes de document
                                </h3>

                                {errors.items && (
                                    <div className="mb-4 rounded-md border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                                        {errors.items}
                                    </div>
                                )}

                                <div className="overflow-x-auto">
                                    <table className="min-w-full text-sm">
                                        <thead>
                                            <tr className="border-b border-border text-start text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                                <th className="pb-2 pe-3">Produit (optionnel)</th>
                                                <th className="pb-2 pe-3">Description *</th>
                                                {isMultiVehicle && (
                                                    <th className="pb-2 pe-3">Véhicule</th>
                                                )}
                                                <th className="pb-2 pe-3">Qté *</th>
                                                <th className="pb-2 pe-3">P.U. HT *</th>
                                                <th className="pb-2 pe-3 text-end">Total HT</th>
                                                <th className="pb-2" />
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-border">
                                            {data.items.map((item, index) => {
                                                const lineTotal =
                                                    (parseFloat(item.quantity as string) || 0) *
                                                    (parseFloat(item.unit_price_ht) || 0);
                                                return (
                                                    <tr key={item._key}>
                                                        <td className="py-2 pe-3">
                                                            <select
                                                                className="w-40 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
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
                                                        {isMultiVehicle && (
                                                            <td className="py-2 pe-3">
                                                                <select
                                                                    className="w-40 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                                                    value={item.vehicle_id}
                                                                    onChange={(e) =>
                                                                        updateItem(
                                                                            index,
                                                                            'vehicle_id',
                                                                            e.target.value,
                                                                        )
                                                                    }
                                                                >
                                                                    <option value="">
                                                                        — Aucun —
                                                                    </option>
                                                                    {filteredVehicles.map((v) => (
                                                                        <option
                                                                            key={v.id}
                                                                            value={v.id}
                                                                        >
                                                                            {v.license_plate} —{' '}
                                                                            {v.make} {v.model}
                                                                        </option>
                                                                    ))}
                                                                </select>
                                                                <InputError
                                                                    message={
                                                                        errors[
                                                                            `items.${index}.vehicle_id`
                                                                        ]
                                                                    }
                                                                    className="mt-0.5"
                                                                />
                                                            </td>
                                                        )}
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
                                                        <td className="py-2 pe-3 text-end font-medium tabular-nums text-foreground">
                                                            {lineTotal.toFixed(2)} DH
                                                        </td>
                                                        <td className="py-2">
                                                            {data.items.length > 1 && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() =>
                                                                        removeItem(index)
                                                                    }
                                                                    className="flex min-h-[44px] min-w-[44px] items-center justify-center rounded text-destructive hover:bg-destructive/10"
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
                                    className="mt-3 inline-flex min-h-[44px] items-center text-sm font-medium text-primary hover:underline"
                                >
                                    + Ajouter une ligne
                                </button>
                            </div>
                        </div>

                        {/* TOTALS + NOTES */}
                        <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                            <div className="p-6">
                                <div className="flex flex-col gap-6 sm:flex-row">
                                    <div className="flex-1">
                                        <InputLabel htmlFor="notes" value="Notes" />
                                        <Textarea
                                            id="notes"
                                            name="notes"
                                            rows={4}
                                            className="mt-1"
                                            value={data.notes}
                                            onChange={(e) => setData('notes', e.target.value)}
                                            placeholder="Remarques, conditions de paiement…"
                                        />
                                        <InputError message={errors.notes} className="mt-1" />
                                    </div>

                                    <div className="w-full sm:w-72">
                                        <div className="space-y-3">
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

                                            <div className="border-t border-border pt-3 text-sm tabular-nums">
                                                <div className="flex justify-between text-muted-foreground">
                                                    <span>Sous-total HT</span>
                                                    <span>{subtotalHt.toFixed(2)} DH</span>
                                                </div>
                                                {discount > 0 && (
                                                    <div className="flex justify-between text-muted-foreground">
                                                        <span>Remise</span>
                                                        <span>− {discount.toFixed(2)} DH</span>
                                                    </div>
                                                )}
                                                <div className="flex justify-between text-muted-foreground">
                                                    <span>TVA ({tvaRate} %)</span>
                                                    <span>{tvaAmount.toFixed(2)} DH</span>
                                                </div>
                                                <div
                                                    className={`mt-2 flex justify-between border-t border-border pt-2 text-base font-bold ${totalTtc < 0 ? 'text-destructive' : 'text-foreground'}`}
                                                >
                                                    <span>Total TTC</span>
                                                    <span>{totalTtc.toFixed(2)} DH</span>
                                                </div>
                                                {totalTtc < 0 && (
                                                    <p className="mt-1 text-xs text-destructive">
                                                        La remise dépasse le sous-total.
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* ACTIONS */}
                        <div className="flex items-center justify-end gap-3">
                            <Button variant="outline" asChild>
                                <Link href={route('invoices.index')}>Annuler</Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing
                                    ? 'Enregistrement…'
                                    : isQuote
                                      ? 'Créer le devis'
                                      : 'Créer la facture'}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
