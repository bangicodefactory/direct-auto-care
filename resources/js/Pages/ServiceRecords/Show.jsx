import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Show({ record, can }) {
    const [confirmingDeletion, setConfirmingDeletion] = useState(false);
    const [processing, setProcessing] = useState(false);

    function destroy() {
        setProcessing(true);
        router.delete(route('service-records.destroy', record.id), {
            onFinish: () => {
                setProcessing(false);
                setConfirmingDeletion(false);
            },
        });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Compte rendu du {record.serviced_at}
                    </h2>
                    <div className="flex gap-2">
                        {can.edit && (
                            <Link
                                href={route('service-records.edit', record.id)}
                                className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Modifier
                            </Link>
                        )}
                        {can.delete && (
                            <button
                                type="button"
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
            <Head title="Compte rendu de service" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl space-y-6 sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="border-b p-6">
                            <h3 className="text-base font-semibold text-gray-900">Informations</h3>
                        </div>
                        <dl className="divide-y divide-gray-100 p-6">
                            <div className="grid grid-cols-2 gap-4 py-3">
                                <dt className="text-sm font-medium text-gray-500">Véhicule</dt>
                                <dd className="text-sm text-gray-900">
                                    <Link
                                        href={route('vehicles.show', record.vehicle.id)}
                                        className="text-primary hover:underline"
                                    >
                                        {record.vehicle.make} {record.vehicle.model}{' '}
                                        <span className="font-mono">
                                            ({record.vehicle.license_plate})
                                        </span>
                                    </Link>
                                </dd>
                            </div>
                            <div className="grid grid-cols-2 gap-4 py-3">
                                <dt className="text-sm font-medium text-gray-500">
                                    Date du service
                                </dt>
                                <dd className="text-sm text-gray-900">{record.serviced_at}</dd>
                            </div>
                            <div className="grid grid-cols-2 gap-4 py-3">
                                <dt className="text-sm font-medium text-gray-500">
                                    Types de service
                                </dt>
                                <dd className="text-sm text-gray-900">
                                    {record.service_types.length > 0 ? (
                                        <div className="flex flex-wrap gap-1">
                                            {record.service_types.map((name) => (
                                                <span
                                                    key={name}
                                                    className="inline-flex items-center rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                                                >
                                                    {name}
                                                </span>
                                            ))}
                                        </div>
                                    ) : (
                                        <span className="text-gray-400">—</span>
                                    )}
                                </dd>
                            </div>
                            {record.mileage_at_service != null && (
                                <div className="grid grid-cols-2 gap-4 py-3">
                                    <dt className="text-sm font-medium text-gray-500">
                                        Kilométrage
                                    </dt>
                                    <dd className="text-sm text-gray-900">
                                        {record.mileage_at_service.toLocaleString('fr-MA')} km
                                    </dd>
                                </div>
                            )}
                            {record.notes && (
                                <div className="grid grid-cols-2 gap-4 py-3">
                                    <dt className="text-sm font-medium text-gray-500">Notes</dt>
                                    <dd className="whitespace-pre-wrap text-sm text-gray-900">
                                        {record.notes}
                                    </dd>
                                </div>
                            )}
                        </dl>
                    </div>

                    <Link
                        href={route('vehicles.show', record.vehicle.id)}
                        className="text-sm text-gray-600 hover:text-gray-900"
                    >
                        ← Retour au véhicule
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
                        Supprimer ce compte rendu ?
                    </h2>
                    <p className="mt-1 text-sm text-gray-600">
                        Le compte rendu du <span className="font-medium">{record.serviced_at}</span>{' '}
                        sera supprimé définitivement.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setConfirmingDeletion(false)}>
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
