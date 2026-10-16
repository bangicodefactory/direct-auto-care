import BulkImportModal from '@/Components/BulkImportModal';
import Modal from '@/Components/Modal';
import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { useQuantityFormat } from '@/hooks/useQuantityFormat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { CATEGORY_LABELS } from '@/lib/utils';
import type { Product, ProductCategory } from '@/types/models';
import type { Paginated } from '@/types/pagination';
import { Head, Link, router } from '@inertiajs/react';
import { MoreVertical } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface ProductRow {
    id: number;
    category: ProductCategory;
    name: string;
    sku: Product['sku'];
    unit: string;
    price_sell: string;
    quantity_on_hand: number;
    low_stock_threshold: number;
    is_low_stock: boolean;
}

interface ProductFilters {
    category?: string;
    low_stock?: string;
    search?: string;
}

interface CategoryOption {
    value: ProductCategory;
    label: string;
}

interface IndexProps {
    products: Paginated<ProductRow>;
    filters: ProductFilters;
    categories: CategoryOption[];
    can: { create: boolean; import: boolean; edit: boolean; delete: boolean };
}

export default function Index({ products, filters, categories, can }: IndexProps) {
    const { formatQuantity } = useQuantityFormat();
    const [search, setSearch] = useState(filters?.search ?? '');
    const [deletingProduct, setDeletingProduct] = useState<ProductRow | null>(null);
    const [importing, setImporting] = useState(false);
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

    function filterCategory(value: string) {
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
        router.delete(route('products.destroy', deletingProduct!.id), {
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
                    <h2 className="text-xl font-semibold leading-tight text-foreground">
                        Produits
                    </h2>
                    <div className="flex items-center gap-2">
                        {can.import && (
                            <Button variant="outline" onClick={() => setImporting(true)}>
                                Importer
                            </Button>
                        )}
                        {can.create && (
                            <Button asChild>
                                <Link href={route('products.create')}>+ Ajouter un produit</Link>
                            </Button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Produits" />

            <div className="py-8">
                <div className="px-4 sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        {/* TOOLBAR */}
                        <div className="flex flex-wrap items-center gap-3 border-b border-border p-4">
                            <TextInput
                                className="flex-1"
                                placeholder="Rechercher par nom ou référence…"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                            <Select
                                value={filters?.category || '__all__'}
                                onValueChange={(v) => filterCategory(v === '__all__' ? '' : v)}
                            >
                                <SelectTrigger className="min-h-[44px] w-52">
                                    <SelectValue placeholder="Toutes catégories" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__all__">Toutes catégories</SelectItem>
                                    {categories.map((c) => (
                                        <SelectItem key={c.value} value={c.value}>
                                            {c.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button
                                type="button"
                                variant={filters?.low_stock ? 'destructive' : 'outline'}
                                onClick={toggleLowStock}
                            >
                                Stock faible seulement
                            </Button>
                            {search && (
                                <Button variant="outline" onClick={() => setSearch('')}>
                                    Effacer
                                </Button>
                            )}
                        </div>

                        {/* TABLE */}
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nom</TableHead>
                                    <TableHead>Réf.</TableHead>
                                    <TableHead>Catégorie</TableHead>
                                    <TableHead className="text-end">Prix vente</TableHead>
                                    <TableHead className="text-end">Qté</TableHead>
                                    <TableHead />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {products.data.map((p) => (
                                    <TableRow key={p.id}>
                                        <TableCell className="whitespace-nowrap font-medium text-foreground">
                                            {p.name}
                                            {p.is_low_stock && (
                                                <span className="ms-2 inline-flex items-center rounded-full bg-destructive/10 px-2 py-0.5 text-xs font-medium text-destructive">
                                                    Stock faible
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-muted-foreground">
                                            {p.sku ?? '—'}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-muted-foreground">
                                            {CATEGORY_LABELS[p.category] ?? p.category}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-end tabular-nums text-foreground">
                                            {Number(p.price_sell).toFixed(2)} DH
                                        </TableCell>
                                        <TableCell
                                            className={`whitespace-nowrap text-end font-semibold tabular-nums ${
                                                p.is_low_stock
                                                    ? 'text-destructive'
                                                    : 'text-foreground'
                                            }`}
                                        >
                                            {formatQuantity(p.quantity_on_hand, p.unit)}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-end">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <button className="flex min-h-[44px] min-w-[44px] items-center justify-center rounded text-muted-foreground hover:bg-accent hover:text-accent-foreground focus:outline-none">
                                                        <MoreVertical className="h-5 w-5" />
                                                    </button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end" className="w-48">
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('products.show', p.id)}>
                                                            Voir
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    {can.edit && (
                                                        <DropdownMenuItem asChild>
                                                            <Link
                                                                href={route('products.edit', p.id)}
                                                            >
                                                                Modifier
                                                            </Link>
                                                        </DropdownMenuItem>
                                                    )}
                                                    {can.delete && (
                                                        <DropdownMenuItem
                                                            className="text-destructive focus:text-destructive"
                                                            onClick={() => setDeletingProduct(p)}
                                                        >
                                                            Supprimer
                                                        </DropdownMenuItem>
                                                    )}
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {products.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            Aucun produit trouvé.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>

                        {/* PAGINATION */}
                        {products.links.length > 3 && (
                            <div className="flex items-center justify-between border-t border-border px-6 py-3">
                                {products.links.map((link, i) =>
                                    link.url ? (
                                        <Link
                                            key={i}
                                            href={link.url}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className={`inline-flex min-h-[44px] items-center px-3 text-sm ${link.active ? 'font-bold text-primary' : 'text-muted-foreground hover:text-foreground'}`}
                                        />
                                    ) : (
                                        <span
                                            key={i}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className="inline-flex min-h-[44px] items-center px-3 text-sm text-muted-foreground/40"
                                        />
                                    ),
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>

            <Modal
                show={deletingProduct !== null}
                maxWidth="sm"
                onClose={() => setDeletingProduct(null)}
            >
                <div className="p-6">
                    <h2 className="text-lg font-medium text-foreground">Supprimer ce produit ?</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        <span className="font-medium">{deletingProduct?.name}</span> sera
                        définitivement supprimé. Cette action est irréversible.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <Button variant="secondary" onClick={() => setDeletingProduct(null)}>
                            Annuler
                        </Button>
                        <Button variant="destructive" onClick={destroy} disabled={processing}>
                            Supprimer définitivement
                        </Button>
                    </div>
                </div>
            </Modal>

            <BulkImportModal
                open={importing}
                onClose={() => setImporting(false)}
                title="Importer des produits"
                templateUrl={route('products.import.template')}
                endpoint={route('products.import')}
            />
        </AuthenticatedLayout>
    );
}
