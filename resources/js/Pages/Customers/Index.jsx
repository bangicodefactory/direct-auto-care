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
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { MoreVertical } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

export default function Index({ customers, filters, can }) {
    const [search, setSearch] = useState(filters?.search ?? '');
    const [deletingCustomer, setDeletingCustomer] = useState(null);
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
                route('customers.index'),
                { search: search || undefined, sort: f?.sort, direction: f?.direction },
                { preserveState: true, replace: true },
            );
        }, 300);
        return () => clearTimeout(timer);
    }, [search]);

    function sortLink(field) {
        const isCurrent = filters?.sort === field;
        const direction = isCurrent && filters?.direction === 'asc' ? 'desc' : 'asc';
        return route('customers.index', { search: filters?.search ?? '', sort: field, direction });
    }

    function sortIcon(field) {
        if (filters?.sort !== field) return null;
        return filters?.direction === 'asc' ? ' ↑' : ' ↓';
    }

    function handleSortChange(value) {
        router.get(
            route('customers.index'),
            { search: filters?.search ?? '', sort: value, direction: 'asc' },
            { preserveState: true, replace: true },
        );
    }

    function destroy() {
        setProcessing(true);
        router.delete(route('customers.destroy', deletingCustomer.id), {
            onFinish: () => {
                setProcessing(false);
                setDeletingCustomer(null);
            },
        });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-foreground">Clients</h2>
                    <Button asChild>
                        <Link href={route('customers.create')}>+ Nouveau client</Link>
                    </Button>
                </div>
            }
        >
            <Head title="Clients" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        {/* TOOLBAR */}
                        <div className="flex flex-wrap items-center gap-3 border-b border-border p-4">
                            <TextInput
                                id="search"
                                name="search"
                                className="flex-1"
                                placeholder="Rechercher par nom, téléphone ou e-mail…"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                            <Select
                                value={filters?.sort || 'last_name'}
                                onValueChange={handleSortChange}
                            >
                                <SelectTrigger className="min-h-[44px] w-52">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="last_name">Trier par : Nom</SelectItem>
                                    <SelectItem value="first_name">Trier par : Prénom</SelectItem>
                                    <SelectItem value="email">Trier par : E-mail</SelectItem>
                                </SelectContent>
                            </Select>
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
                                    <TableHead>
                                        <Link
                                            href={sortLink('last_name')}
                                            className="inline-flex min-h-[44px] items-center text-xs font-medium uppercase tracking-wider"
                                        >
                                            Nom{sortIcon('last_name')}
                                        </Link>
                                    </TableHead>
                                    <TableHead>
                                        <Link
                                            href={sortLink('first_name')}
                                            className="inline-flex min-h-[44px] items-center text-xs font-medium uppercase tracking-wider"
                                        >
                                            Prénom{sortIcon('first_name')}
                                        </Link>
                                    </TableHead>
                                    <TableHead>
                                        <Link
                                            href={sortLink('email')}
                                            className="inline-flex min-h-[44px] items-center text-xs font-medium uppercase tracking-wider"
                                        >
                                            E-mail{sortIcon('email')}
                                        </Link>
                                    </TableHead>
                                    <TableHead>Téléphone</TableHead>
                                    <TableHead />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {customers.data.map((c) => (
                                    <TableRow key={c.id}>
                                        <TableCell className="whitespace-nowrap font-medium text-foreground">
                                            {c.last_name}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-muted-foreground">
                                            {c.first_name}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-muted-foreground">
                                            {c.email ?? '—'}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-muted-foreground">
                                            {c.phone}
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
                                                        <Link href={route('customers.show', c.id)}>
                                                            Voir
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('customers.edit', c.id)}>
                                                            Modifier
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    {can.delete && (
                                                        <DropdownMenuItem
                                                            className="text-destructive focus:text-destructive"
                                                            onClick={() => setDeletingCustomer(c)}
                                                        >
                                                            Supprimer
                                                        </DropdownMenuItem>
                                                    )}
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {customers.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            Aucun client trouvé.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>

                        {/* PAGINATION */}
                        {customers.links.length > 3 && (
                            <div className="flex items-center justify-between border-t border-border px-6 py-3">
                                {customers.links.map((link, i) =>
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
                show={deletingCustomer !== null}
                maxWidth="sm"
                onClose={() => setDeletingCustomer(null)}
            >
                <div className="p-6">
                    <h2 className="text-lg font-medium text-foreground">Supprimer ce client ?</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        <span className="font-medium">
                            {deletingCustomer?.first_name} {deletingCustomer?.last_name}
                        </span>{' '}
                        sera définitivement supprimé. Cette action est irréversible.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <Button variant="secondary" onClick={() => setDeletingCustomer(null)}>
                            Annuler
                        </Button>
                        <Button variant="destructive" onClick={destroy} disabled={processing}>
                            Supprimer définitivement
                        </Button>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
