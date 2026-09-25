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
import { useShowUnits } from '@/hooks/useShowUnits';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { ProductCategory, ProductUnit } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

interface ProductForEdit {
    id: number;
    category: ProductCategory;
    name: string;
    sku: string | null;
    unit: string;
    price_buy: string | null;
    price_sell: string;
    quantity_on_hand: number;
    low_stock_threshold: number;
}

interface CategoryOption {
    value: ProductCategory;
    label: string;
}

interface UnitOption {
    value: ProductUnit;
    label: string;
}

interface EditProps {
    product: ProductForEdit;
    categories: CategoryOption[];
    units: UnitOption[];
}

export default function Edit({ product, categories, units }: EditProps) {
    const showUnits = useShowUnits();
    const { data, setData, put, processing, errors } = useForm<{
        category: string;
        name: string;
        sku: string;
        unit: string;
        price_buy: string;
        price_sell: string;
        quantity_on_hand: number | string;
        low_stock_threshold: number | string;
    }>({
        category: product.category,
        name: product.name,
        sku: product.sku ?? '',
        unit: product.unit,
        price_buy: product.price_buy ?? '',
        price_sell: product.price_sell,
        quantity_on_hand: product.quantity_on_hand,
        low_stock_threshold: product.low_stock_threshold,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        put(route('products.update', product.id));
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-foreground">
                    Modifier — {product.name}
                </h2>
            }
        >
            <Head title={`Modifier ${product.name}`} />

            <div className="py-8">
                <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        className="space-y-5 rounded-lg border border-border bg-card p-6 shadow-sm"
                    >
                        <div>
                            <InputLabel htmlFor="category" value="Catégorie *" />
                            <Select
                                value={data.category}
                                onValueChange={(v) => setData('category', v)}
                            >
                                <SelectTrigger id="category" className="mt-1 min-h-[44px] w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {categories.map((c) => (
                                        <SelectItem key={c.value} value={c.value}>
                                            {c.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.category} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="name" value="Nom *" />
                            <TextInput
                                id="name"
                                className="mt-1 block w-full"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                            />
                            <InputError message={errors.name} className="mt-1" />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel htmlFor="sku" value="Référence" />
                                <TextInput
                                    id="sku"
                                    className="mt-1 block w-full"
                                    value={data.sku}
                                    onChange={(e) => setData('sku', e.target.value)}
                                />
                                <InputError message={errors.sku} className="mt-1" />
                            </div>
                            {showUnits && (
                                <div>
                                    <InputLabel htmlFor="unit" value="Unité *" />
                                    {/* No auto-default on category change here: the
                                    existing unit was chosen deliberately and
                                    recategorizing must not silently swap it. */}
                                    <Select
                                        value={data.unit}
                                        onValueChange={(v) => setData('unit', v)}
                                    >
                                        <SelectTrigger
                                            id="unit"
                                            className="mt-1 min-h-[44px] w-full"
                                        >
                                            <SelectValue placeholder="— Choisir —" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {units.map((u) => (
                                                <SelectItem key={u.value} value={u.value}>
                                                    {u.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.unit} className="mt-1" />
                                </div>
                            )}
                            {/* Field hidden: a server-side unit error must still surface,
                                or the save fails silently. */}
                            {!showUnits && errors.unit && (
                                <InputError message={errors.unit} className="mt-1" />
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel htmlFor="price_buy" value="Prix d'achat (DH)" />
                                <TextInput
                                    id="price_buy"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    className="mt-1 block w-full"
                                    value={data.price_buy}
                                    onChange={(e) => setData('price_buy', e.target.value)}
                                />
                                <InputError message={errors.price_buy} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel htmlFor="price_sell" value="Prix de vente (DH) *" />
                                <TextInput
                                    id="price_sell"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    className="mt-1 block w-full"
                                    value={data.price_sell}
                                    onChange={(e) => setData('price_sell', e.target.value)}
                                />
                                <InputError message={errors.price_sell} className="mt-1" />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel
                                    htmlFor="quantity_on_hand"
                                    value="Quantité en stock *"
                                />
                                <TextInput
                                    id="quantity_on_hand"
                                    type="number"
                                    min="0"
                                    className="mt-1 block w-full"
                                    value={data.quantity_on_hand}
                                    onChange={(e) => setData('quantity_on_hand', e.target.value)}
                                />
                                <InputError message={errors.quantity_on_hand} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel
                                    htmlFor="low_stock_threshold"
                                    value="Seuil d'alerte *"
                                />
                                <TextInput
                                    id="low_stock_threshold"
                                    type="number"
                                    min="1"
                                    className="mt-1 block w-full"
                                    value={data.low_stock_threshold}
                                    onChange={(e) => setData('low_stock_threshold', e.target.value)}
                                />
                                <InputError message={errors.low_stock_threshold} className="mt-1" />
                            </div>
                        </div>

                        <div className="flex items-center justify-between pt-2">
                            <Link
                                href={route('products.show', product.id)}
                                className="text-sm text-muted-foreground hover:underline"
                            >
                                Annuler
                            </Link>
                            <Button disabled={processing}>Enregistrer</Button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
