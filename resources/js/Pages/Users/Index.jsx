import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

const roleLabels = { admin: 'Administrateur', employee: 'Employé' };

export default function Index({ users }) {
    const [deletingUser, setDeletingUser] = useState(null);
    const [processing, setProcessing] = useState(false);

    function destroy() {
        setProcessing(true);
        router.delete(route('users.destroy', deletingUser.id), {
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
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Utilisateurs
                    </h2>
                    <Link
                        href={route('users.create')}
                        className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        Créer un utilisateur
                    </Link>
                </div>
            }
        >
            <Head title="Utilisateurs" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Nom
                                    </th>
                                    <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Adresse e-mail
                                    </th>
                                    <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Rôle
                                    </th>
                                    <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Statut
                                    </th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {users.data.map((user) => (
                                    <tr key={user.id}>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                            {user.name}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {user.email}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {roleLabels[user.roles[0]] ?? '—'}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm">
                                            {user.is_active ? (
                                                <span className="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">
                                                    Actif
                                                </span>
                                            ) : (
                                                <span className="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800">
                                                    Inactif
                                                </span>
                                            )}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-end text-sm font-medium">
                                            <Link
                                                href={route('users.edit', user.id)}
                                                className="me-4 text-primary hover:text-primary/80"
                                            >
                                                Modifier
                                            </Link>
                                            <button
                                                onClick={() => setDeletingUser(user)}
                                                className="text-red-600 hover:text-red-800"
                                            >
                                                Supprimer
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                                {users.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-6 py-8 text-center text-sm text-gray-500"
                                        >
                                            Aucun utilisateur trouvé.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>

                        {users.links.length > 3 && (
                            <div className="flex items-center justify-between border-t border-gray-200 px-6 py-3">
                                {users.links.map((link, i) =>
                                    link.url ? (
                                        <Link
                                            key={i}
                                            href={link.url}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className={`px-3 py-1 text-sm ${link.active ? 'font-bold text-primary' : 'text-gray-500 hover:text-gray-700'}`}
                                        />
                                    ) : (
                                        <span
                                            key={i}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className="px-3 py-1 text-sm text-gray-300"
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
                    <h2 className="text-lg font-medium text-gray-900">
                        Supprimer cet utilisateur ?
                    </h2>
                    <p className="mt-1 text-sm text-gray-600">
                        <span className="font-medium">{deletingUser?.name}</span> sera
                        définitivement supprimé. Cette action est irréversible.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setDeletingUser(null)}>
                            Annuler
                        </SecondaryButton>
                        <DangerButton onClick={destroy} disabled={processing}>
                            Supprimer définitivement
                        </DangerButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
