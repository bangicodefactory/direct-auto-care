import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { useShowUnits } from '@/hooks/useShowUnits';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { CATEGORY_LABELS, formatQuantity, pluralizeUnit } from '@/lib/utils';
import type { ProductCategory, StockMovementType } from '@/types/models';
import { Head, Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';

const TYPE_LABELS: Record<StockMovementType, string> = {
    in: 'Entrée',
    out: 'Sortie',
    adjustment: 'Ajustement',
};

interface ProductDetail {
    id: number;
    category: ProductCategory;
    name: string;
    sku: string | null;
    unit: string;
    price_buy: string | null;
    price_sell: string;
    quantity_on_hand: number;
    low_stock_threshold: number;
    is_low_stock: boolean;
}

interface MovementRow {
    id: number;
    type: StockMovementType;
    quantity: number;
    reason: string | null;
    user: string | null;
    created_at: string;
}

interface ShowProps {
    product: ProductDetail;
    movements: MovementRow[];
    can: { edit: boolean; delete: boolean; adjust: boolean };
}

export default function Show({ product, movements, can }: ShowProps) {
    const showUnits = useShowUnits();
    const [confirmDelete, setConfirmDelete] = useState(false);
    const [adjusting, setAdjusting] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        quantity: '',
        reason: '',
    });

    function handleDelete() {
        setConfirmDelete(false);
        router.delete(route('products.destroy', product.id));
    }

    function submitAdjustment(e: FormEvent) {
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
                <h2 className="text-xl font-semibold leading-tight text-foreground">
                    {product.name}
                </h2>
            }
        >
            <Head title={product.name} />

            <div className="py-8">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    {/* Product info */}
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <dl className="divide-y divide-border">
                            {[
                                [
                                    'Catégorie',
                                    CATEGORY_LABELS[product.category] ?? product.category,
                                ],
                                ['Référence', product.sku ?? '—'],
                                ...(showUnits ? [['Unité', pluralizeUnit(1, product.unit)]] : []),
                                [
                                    "Prix d'achat",
                                    product.price_buy
                                        ? `${Number(product.price_buy).toFixed(2)} DH`
                                        : '—',
                                ],
                                ['Prix de vente', `${Number(product.price_sell).toFixed(2)} DH`],
                                [
                                    'Quantité en stock',
                                    formatQuantity(
                                        product.quantity_on_hand,
                                        product.unit,
                                        showUnits,
                                    ),
                                ],
                                ["Seuil d'alerte", product.low_stock_threshold],
                            ].map(([label, value]) => (
                                <div key={label} className="grid grid-cols-3 gap-4 px-6 py-4">
                                    <dt className="text-sm font-medium text-muted-foreground">
                                        {label}
                                    </dt>
                                    <dd className="col-span-2 text-sm tabular-nums text-foreground">
                                        {value}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </div>

                    {product.is_low_stock && (
                        <p className="mt-3 rounded-md border border-destructive/20 bg-destructive/10 px-4 py-2 text-sm font-medium text-destructive">
                            Stock faible — réapprovisionnement recommandé.
                        </p>
                    )}

                    {/* Actions */}
                    <div className="mt-6 flex flex-wrap gap-3">
                        <Link
                            href={route('products.index')}
                            className="text-sm text-muted-foreground hover:underline"
                        >
                            ← Retour
                        </Link>

                        {can.edit && (
                            <Button asChild>
                                <Link href={route('products.edit', product.id)}>Modifier</Link>
                            </Button>
                        )}

                        {can.adjust && (
                            <Button variant="secondary" onClick={() => setAdjusting(true)}>
                                Ajuster le stock
                            </Button>
                        )}

                        {can.delete && !confirmDelete && (
                            <Button variant="destructive" onClick={() => setConfirmDelete(true)}>
                                Supprimer
                            </Button>
                        )}
                    </div>

                    {/* Stock movements log */}
                    <div className="mt-10">
                        <h3 className="mb-3 text-sm font-semibold uppercase tracking-wider text-muted-foreground">
                            Derniers mouvements de stock
                        </h3>
                        <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead className="text-end">Quantité</TableHead>
                                        <TableHead>Motif</TableHead>
                                        <TableHead>Par</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {movements.map((m) => (
                                        <TableRow key={m.id}>
                                            <TableCell className="whitespace-nowrap text-muted-foreground">
                                                {m.created_at}
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap text-foreground">
                                                {TYPE_LABELS[m.type] ?? m.type}
                                            </TableCell>
                                            <TableCell
                                                className={`whitespace-nowrap text-end font-semibold tabular-nums ${m.quantity > 0 ? 'text-emerald-600' : 'text-destructive'}`}
                                            >
                                                {m.quantity > 0 ? `+${m.quantity}` : m.quantity}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {m.reason ?? '—'}
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap text-muted-foreground">
                                                {m.user ?? '—'}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {movements.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={5}
                                                className="py-6 text-center text-muted-foreground"
                                            >
                                                Aucun mouvement de stock enregistré.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </div>
            </div>

            <Modal show={confirmDelete} maxWidth="sm" onClose={() => setConfirmDelete(false)}>
                <div className="p-6">
                    <h2 className="text-lg font-medium text-foreground">Supprimer le produit</h2>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Êtes-vous sûr de vouloir supprimer{' '}
                        <span className="font-semibold">{product.name}</span> ? Cette action est
                        irréversible.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <Button variant="secondary" onClick={() => setConfirmDelete(false)}>
                            Annuler
                        </Button>
                        <Button variant="destructive" onClick={handleDelete}>
                            Supprimer définitivement
                        </Button>
                    </div>
                </div>
            </Modal>

            <Modal show={adjusting} maxWidth="sm" onClose={() => setAdjusting(false)}>
                <form onSubmit={submitAdjustment} className="p-6">
                    <h2 className="text-lg font-medium text-foreground">Ajuster le stock</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Stock actuel :{' '}
                        <span className="font-semibold">
                            {formatQuantity(product.quantity_on_hand, product.unit, showUnits)}
                        </span>
                        . Entrez une valeur positive pour ajouter, négative pour retirer.
                    </p>

                    <div className="mt-4">
                        <InputLabel htmlFor="adj-quantity" value="Quantité *" />
                        <TextInput
                            id="adj-quantity"
                            type="number"
                            className="mt-1 w-full"
                            value={data.quantity}
                            onChange={(e) => setData('quantity', e.target.value)}
                            autoFocus
                        />
                        <InputError message={errors.quantity} className="mt-1" />
                    </div>

                    <div className="mt-4">
                        <InputLabel htmlFor="adj-reason" value="Motif (optionnel)" />
                        <TextInput
                            id="adj-reason"
                            type="text"
                            className="mt-1 w-full"
                            placeholder="Ex. : Inventaire physique, casse…"
                            value={data.reason}
                            onChange={(e) => setData('reason', e.target.value)}
                        />
                        <InputError message={errors.reason} className="mt-1" />
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <Button
                            variant="secondary"
                            type="button"
                            onClick={() => setAdjusting(false)}
                        >
                            Annuler
                        </Button>
                        <Button type="submit" disabled={processing}>
                            Enregistrer
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
