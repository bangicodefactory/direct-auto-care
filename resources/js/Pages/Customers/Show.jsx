import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Show({ customer, can }) {
    const [confirmingDeletion, setConfirmingDeletion] = useState(false);

    function destroy() {
        router.delete(route('customers.destroy', customer.id), {
            onFinish: () => setConfirmingDeletion(false),
        });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        {customer.first_name} {customer.last_name}
                    </h2>
                    <div className="flex gap-3">
                        <Link
                            href={route('customers.edit', customer.id)}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            Modifier
                        </Link>
                        {can.delete && (
                            <button
                                onClick={() => setConfirmingDeletion(true)}
                                className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                            >
                                Supprimer
                            </button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`${customer.first_name} ${customer.last_name}`} />

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    {/* INFO CLIENT */}
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="mb-4 text-lg font-medium text-gray-900">
                                Informations
                            </h3>
                            <dl className="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                                <dt className="font-medium text-gray-500">Téléphone</dt>
                                <dd className="text-gray-900">{customer.phone}</dd>

                                <dt className="font-medium text-gray-500">Adresse e-mail</dt>
                                <dd className="text-gray-900">{customer.email ?? '—'}</dd>

                                <dt className="font-medium text-gray-500">Adresse</dt>
                                <dd className="text-gray-900">{customer.address ?? '—'}</dd>

                                <dt className="font-medium text-gray-500">Notes</dt>
                                <dd className="text-gray-900">{customer.notes ?? '—'}</dd>
                            </dl>
                        </div>
                    </div>

                    {/* VÉHICULES */}
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="mb-4 text-lg font-medium text-gray-900">Véhicules</h3>
                            {customer.vehicles?.length > 0 ? (
                                <ul className="divide-y divide-gray-200">
                                    {customer.vehicles.map((v) => (
                                        <li key={v.id} className="py-2 text-sm text-gray-700">
                                            {v.make} {v.model} — {v.license_plate}
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-sm text-gray-500">Aucun véhicule enregistré.</p>
                            )}
                        </div>
                    </div>

                    {/* RENDEZ-VOUS */}
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="mb-4 text-lg font-medium text-gray-900">Rendez-vous</h3>
                            {customer.appointments?.length > 0 ? (
                                <ul className="divide-y divide-gray-200">
                                    {customer.appointments.map((a) => (
                                        <li key={a.id} className="py-2 text-sm text-gray-700">
                                            {a.scheduled_at}
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-sm text-gray-500">Aucun rendez-vous.</p>
                            )}
                        </div>
                    </div>

                    {/* FACTURES */}
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="mb-4 text-lg font-medium text-gray-900">Factures</h3>
                            {customer.invoices?.length > 0 ? (
                                <ul className="divide-y divide-gray-200">
                                    {customer.invoices.map((inv) => (
                                        <li key={inv.id} className="py-2 text-sm text-gray-700">
                                            #{inv.id}
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-sm text-gray-500">Aucune facture.</p>
                            )}
                        </div>
                    </div>

                    <Link
                        href={route('customers.index')}
                        className="text-sm text-gray-600 hover:text-gray-900"
                    >
                        ← Retour à la liste
                    </Link>
                </div>
            </div>

            <Modal
                show={confirmingDeletion}
                maxWidth="sm"
                onClose={() => setConfirmingDeletion(false)}
            >
                <div className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">
                        Supprimer ce client ?
                    </h2>
                    <p className="mt-1 text-sm text-gray-600">
                        Cette action est irréversible. Toutes les données associées seront
                        définitivement supprimées.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setConfirmingDeletion(false)}>
                            Annuler
                        </SecondaryButton>
                        <DangerButton onClick={destroy}>
                            Supprimer définitivement
                        </DangerButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
