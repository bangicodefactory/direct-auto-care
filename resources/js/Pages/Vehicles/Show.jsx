import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import { FUEL_LABELS } from '@/constants/fuelTypes';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

const TABS = [
    { key: 'history', label: 'Historique de service' },
    { key: 'appointments', label: 'Rendez-vous' },
    { key: 'documents', label: 'Documents' },
];

function EmptyState({ message }) {
    return <p className="text-sm text-gray-500">{message}</p>;
}

function ServiceHistoryTab({ records, onDeleteRecord }) {
    if (!records.length) return <EmptyState message="Aucun historique de service enregistré." />;

    return (
        <ul className="divide-y divide-gray-100">
            {records.map((r) => (
                <li key={r.id} className="py-4">
                    <div className="flex items-start justify-between gap-4">
                        <div className="min-w-0 space-y-1">
                            <p className="text-sm font-semibold text-gray-900">{r.serviced_at}</p>

                            {r.service_types.length > 0 && (
                                <div className="flex flex-wrap gap-1">
                                    {r.service_types.map((name) => (
                                        <span
                                            key={name}
                                            className="inline-flex items-center rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                                        >
                                            {name}
                                        </span>
                                    ))}
                                </div>
                            )}

                            {r.mileage_at_service != null && (
                                <p className="text-sm text-gray-500">
                                    {r.mileage_at_service.toLocaleString('fr-MA')} km
                                </p>
                            )}

                            {r.notes && (
                                <p className="line-clamp-2 text-sm text-gray-500">{r.notes}</p>
                            )}
                        </div>

                        <div className="flex shrink-0 gap-3">
                            {r.can_edit && (
                                <Link
                                    href={route('service-records.edit', r.id)}
                                    className="text-sm text-primary hover:underline"
                                >
                                    Modifier
                                </Link>
                            )}
                            {r.can_delete && (
                                <button
                                    type="button"
                                    onClick={() => onDeleteRecord(r)}
                                    className="text-sm text-red-600 hover:text-red-700"
                                >
                                    Supprimer
                                </button>
                            )}
                        </div>
                    </div>
                </li>
            ))}
        </ul>
    );
}

function AppointmentsTab({ appointments }) {
    if (!appointments.length) return <EmptyState message="Aucun rendez-vous enregistré." />;
    return (
        <ul className="divide-y divide-gray-100">
            {appointments.map((a) => (
                <li key={a.id} className="py-3 text-sm text-gray-700">
                    Rendez-vous du {a.created_at}
                </li>
            ))}
        </ul>
    );
}

function DocumentsTab() {
    return <EmptyState message="Module documents à venir." />;
}

export default function Show({ vehicle, can }) {
    const [activeTab, setActiveTab] = useState('history');
    const [confirmingDeletion, setConfirmingDeletion] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [deletingRecord, setDeletingRecord] = useState(null);
    const [processingRecord, setProcessingRecord] = useState(false);

    function destroy() {
        setProcessing(true);
        router.delete(route('vehicles.destroy', vehicle.id), {
            onFinish: () => {
                setProcessing(false);
                setConfirmingDeletion(false);
            },
        });
    }

    function destroyRecord() {
        setProcessingRecord(true);
        router.delete(route('service-records.destroy', deletingRecord.id), {
            onFinish: () => {
                setProcessingRecord(false);
                setDeletingRecord(null);
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
                        {can.create_service_record && (
                            <Link
                                href={route('vehicles.service-records.create', vehicle.id)}
                                className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                            >
                                Nouveau compte rendu
                            </Link>
                        )}
                        <Link
                            href={route('vehicles.edit', vehicle.id)}
                            className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
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
                    {/* INFO CARD */}
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

                    {/* TABS */}
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="border-b border-gray-200">
                            <nav
                                className="-mb-px flex overflow-x-auto"
                                role="tablist"
                                aria-label="Sections du véhicule"
                            >
                                {TABS.map((tab) => (
                                    <button
                                        key={tab.key}
                                        id={`tab-${tab.key}`}
                                        type="button"
                                        role="tab"
                                        aria-selected={activeTab === tab.key}
                                        aria-controls={`tabpanel-${tab.key}`}
                                        onClick={() => setActiveTab(tab.key)}
                                        className={`whitespace-nowrap border-b-2 px-6 py-4 text-sm font-medium transition-colors ${
                                            activeTab === tab.key
                                                ? 'border-primary text-primary'
                                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                                        }`}
                                    >
                                        {tab.label}
                                    </button>
                                ))}
                            </nav>
                        </div>
                        <div
                            role="tabpanel"
                            id={`tabpanel-${activeTab}`}
                            aria-labelledby={`tab-${activeTab}`}
                            className="p-6"
                        >
                            {activeTab === 'history' && (
                                <ServiceHistoryTab
                                    records={vehicle.service_records}
                                    onDeleteRecord={setDeletingRecord}
                                />
                            )}
                            {activeTab === 'appointments' && (
                                <AppointmentsTab appointments={vehicle.appointments} />
                            )}
                            {activeTab === 'documents' && <DocumentsTab />}
                        </div>
                    </div>

                    <Link
                        href={route('vehicles.index')}
                        className="text-sm text-gray-600 hover:text-gray-900"
                    >
                        ← Retour à la liste
                    </Link>
                </div>
            </div>

            {/* Vehicle delete modal */}
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

            {/* Service record delete modal */}
            <Modal show={!!deletingRecord} maxWidth="sm" onClose={() => setDeletingRecord(null)}>
                <div className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">
                        Supprimer ce compte rendu ?
                    </h2>
                    <p className="mt-1 text-sm text-gray-600">
                        Le compte rendu du{' '}
                        <span className="font-medium">{deletingRecord?.serviced_at}</span> sera
                        supprimé définitivement.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setDeletingRecord(null)}>
                            Annuler
                        </SecondaryButton>
                        <DangerButton onClick={destroyRecord} disabled={processingRecord}>
                            Supprimer
                        </DangerButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
