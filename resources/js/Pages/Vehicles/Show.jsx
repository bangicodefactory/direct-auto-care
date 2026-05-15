import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

const FUEL_LABELS = {
    essence: 'Essence',
    diesel: 'Diesel',
    hybride: 'Hybride',
    electrique: 'Électrique',
};

export default function Show({ vehicle, can }) {
    const [confirmingDeletion, setConfirmingDeletion] = useState(false);
    const [processing, setProcessing] = useState(false);

    function destroy() {
        setProcessing(true);
        router.delete(route('vehicles.destroy', vehicle.id), {
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
                        {vehicle.make} {vehicle.model}{' '}
                        <span className="font-normal text-gray-500">({vehicle.year})</span>
                    </h2>
                    <div className="flex gap-2">
                        <Link
                            href={route('vehicles.edit', vehicle.id)}
                            className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            Modifier
                        </Link>
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
            <Head title={`${vehicle.make} ${vehicle.model}`} />

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="border-b p-6">
                            <h3 className="text-base font-semibold text-gray-900">Informations</h3>
                        </div>
                        <dl className="divide-y divide-gray-100 p-6">
                            <div className="grid grid-cols-2 gap-4 py-3">
                                <dt className="text-sm font-medium text-gray-500">Plaque</dt>
                                <dd className="font-mono text-sm text-gray-900">
                                    {vehicle.license_plate}
                                </dd>
                            </div>
                            <div className="grid grid-cols-2 gap-4 py-3">
                                <dt className="text-sm font-medium text-gray-500">
                                    Marque / Modèle
                                </dt>
                                <dd className="text-sm text-gray-900">
                                    {vehicle.make} {vehicle.model}
                                </dd>
                            </div>
                            <div className="grid grid-cols-2 gap-4 py-3">
                                <dt className="text-sm font-medium text-gray-500">Année</dt>
                                <dd className="text-sm text-gray-900">{vehicle.year}</dd>
                            </div>
                            <div className="grid grid-cols-2 gap-4 py-3">
                                <dt className="text-sm font-medium text-gray-500">Carburant</dt>
                                <dd className="text-sm text-gray-900">
                                    {FUEL_LABELS[vehicle.fuel_type] ?? vehicle.fuel_type}
                                </dd>
                            </div>
                            <div className="grid grid-cols-2 gap-4 py-3">
                                <dt className="text-sm font-medium text-gray-500">Kilométrage</dt>
                                <dd className="text-sm text-gray-900">
                                    {vehicle.mileage.toLocaleString('fr-MA')} km
                                </dd>
                            </div>
                            {vehicle.color && (
                                <div className="grid grid-cols-2 gap-4 py-3">
                                    <dt className="text-sm font-medium text-gray-500">Couleur</dt>
                                    <dd className="text-sm text-gray-900">{vehicle.color}</dd>
                                </div>
                            )}
                            {vehicle.vin && (
                                <div className="grid grid-cols-2 gap-4 py-3">
                                    <dt className="text-sm font-medium text-gray-500">
                                        Numéro de châssis
                                    </dt>
                                    <dd className="font-mono text-sm text-gray-900">
                                        {vehicle.vin}
                                    </dd>
                                </div>
                            )}
                            <div className="grid grid-cols-2 gap-4 py-3">
                                <dt className="text-sm font-medium text-gray-500">Propriétaire</dt>
                                <dd className="text-sm text-gray-900">
                                    <Link
                                        href={route('customers.show', vehicle.customer.id)}
                                        className="text-primary hover:underline"
                                    >
                                        {vehicle.customer.first_name} {vehicle.customer.last_name}
                                    </Link>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <Modal
                show={confirmingDeletion}
                maxWidth="sm"
                onClose={() => setConfirmingDeletion(false)}
            >
                <div className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">Supprimer ce véhicule ?</h2>
                    <p className="mt-1 text-sm text-gray-600">
                        <span className="font-medium">
                            {vehicle.make} {vehicle.model} — {vehicle.license_plate}
                        </span>{' '}
                        sera supprimé. Cette action est irréversible.
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
