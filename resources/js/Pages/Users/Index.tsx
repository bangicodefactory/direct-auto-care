import Modal from '@/Components/Modal';
import { Button } from '@/Components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Paginated } from '@/types/pagination';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

const roleLabels: Record<string, string> = { admin: 'Administrateur', employee: 'Employé' };

interface UserRow {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    roles: string[];
}

interface IndexProps {
    users: Paginated<UserRow>;
}

export default function Index({ users }: IndexProps) {
    const [deletingUser, setDeletingUser] = useState<UserRow | null>(null);
    const [processing, setProcessing] = useState(false);

    function destroy() {
        setProcessing(true);
        router.delete(route('users.destroy', deletingUser!.id), {
            onFinish: () => {
                setProcessing(false);
                setDeletingUser(null);
            },
        });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-foreground">
                        Utilisateurs
                    </h2>
                    <Button asChild>
                        <Link href={route('users.create')}>Créer un utilisateur</Link>
                    </Button>
                </div>
            }
        >
            <Head title="Utilisateurs" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nom</TableHead>
                                    <TableHead>Adresse e-mail</TableHead>
                                    <TableHead>Rôle</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.data.map((user) => (
                                    <TableRow key={user.id}>
                                        <TableCell className="whitespace-nowrap font-medium text-foreground">
                                            {user.name}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-muted-foreground">
                                            {user.email}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-muted-foreground">
                                            {roleLabels[user.roles[0]] ?? '—'}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap">
                                            {user.is_active ? (
                                                <span className="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">
                                                    Actif
                                                </span>
                                            ) : (
                                                <span className="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800">
                                                    Inactif
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-end">
                                            <Link
                                                href={route('users.edit', user.id)}
                                                className="inline-flex min-h-[44px] items-center me-4 text-primary hover:text-primary/80"
                                            >
                                                Modifier
                                            </Link>
                                            <button
                                                onClick={() => setDeletingUser(user)}
                                                className="inline-flex min-h-[44px] items-center text-destructive hover:text-destructive/80"
                                            >
                                                Supprimer
                                            </button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {users.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            Aucun utilisateur trouvé.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>

                        {users.links.length > 3 && (
                            <div className="flex items-center justify-between border-t border-border px-6 py-3">
                                {users.links.map((link, i) =>
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

            {/* DELETE MODAL */}
            <Modal show={deletingUser !== null} maxWidth="sm" onClose={() => setDeletingUser(null)}>
                <div className="p-6">
                    <h2 className="text-lg font-medium text-foreground">
                        Supprimer cet utilisateur ?
                    </h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        <span className="font-medium">{deletingUser?.name}</span> sera
                        définitivement supprimé. Cette action est irréversible.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <Button variant="secondary" onClick={() => setDeletingUser(null)}>
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
