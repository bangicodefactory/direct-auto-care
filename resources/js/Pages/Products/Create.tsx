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
import { useRef } from 'react';

interface CategoryOption {
    value: ProductCategory;
    label: string;
}

interface UnitOption {
    value: ProductUnit;
    label: string;
}

interface CreateProps {
    categories: CategoryOption[];
    units: UnitOption[];
    unitDefaults: Record<ProductCategory, ProductUnit>;
}

export default function Create({ categories, units, unitDefaults }: CreateProps) {
    const showUnits = useShowUnits();
    // Picking a category pre-selects its usual unit (huile → litre), but only
    // until the user touches the unit themselves — their explicit choice must
    // survive later category changes.
    const unitTouched = useRef(false);

    const { data, setData, post, processing, errors } = useForm<{
        category: string;
        name: string;
        sku: string;
        unit: string;
        price_buy: string;
        price_sell: string;
        quantity_on_hand: number | string;
        low_stock_threshold: number | string;
    }>({
        category: '',
        name: '',
        sku: '',
        unit: '',
        price_buy: '',
        price_sell: '',
        quantity_on_hand: 0,
        low_stock_threshold: 5,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        post(route('products.store'));
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-foreground">
                    Ajouter un produit
                </h2>
            }
        >
            <Head title="Nouveau produit" />

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
                                onValueChange={(v) =>
                                    setData((d) => ({
                                        ...d,
                                        category: v,
                                        unit: unitTouched.current
                                            ? d.unit
                                            : unitDefaults[v as ProductCategory],
                                    }))
                                }
                            >
                                <SelectTrigger id="category" className="mt-1 min-h-[44px] w-full">
                                    <SelectValue placeholder="— Choisir —" />
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
                                    <Select
                                        value={data.unit}
                                        onValueChange={(v) => {
                                            unitTouched.current = true;
                                            setData('unit', v);
                                        }}
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
                                href={route('products.index')}
                                className="text-sm text-muted-foreground hover:underline"
                            >
                                Annuler
                            </Link>
                            <Button disabled={processing}>Créer</Button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
