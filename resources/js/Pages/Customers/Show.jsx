import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

const TABS = [
    { key: 'vehicles', label: 'Véhicules' },
    { key: 'history', label: 'Historique' },
    { key: 'invoices', label: 'Factures' },
    { key: 'appointments', label: 'Rendez-vous' },
];

function EmptyState({ message }) {
    return <p className="text-sm text-gray-500">{message}</p>;
}

function VehiclesTab({ vehicles }) {
    if (!vehicles?.length) return <EmptyState message="Aucun véhicule enregistré." />;
    return (
        <ul className="divide-y divide-gray-200">
            {vehicles.map((v) => (
                <li key={v.id} className="py-3 text-sm text-gray-700">
                    {[v.make, v.model].filter(Boolean).join(' ') || `Véhicule #${v.id}`}
                    {v.license_plate ? ` — ${v.license_plate}` : ''}
                </li>
            ))}
        </ul>
    );
}

function HistoryTab() {
    return <EmptyState message="Aucun historique d'intervention." />;
}

function InvoicesTab({ invoices }) {
    if (!invoices?.length) return <EmptyState message="Aucune facture." />;
    return (
        <ul className="divide-y divide-gray-200">
            {invoices.map((inv) => (
                <li key={inv.id} className="py-3 text-sm text-gray-700">
                    Facture #{inv.id}
                </li>
            ))}
        </ul>
    );
}

function AppointmentsTab({ appointments }) {
    if (!appointments?.length) return <EmptyState message="Aucun rendez-vous." />;
    return (
        <ul className="divide-y divide-gray-200">
            {appointments.map((a) => (
                <li key={a.id} className="py-3 text-sm text-gray-700">
                    Rendez-vous #{a.id}
                </li>
            ))}
        </ul>
    );
}

export default function Show({ customer, can }) {
    const [activeTab, setActiveTab] = useState('vehicles');
    const [confirmingDeletion, setConfirmingDeletion] = useState(false);
    const [processing, setProcessing] = useState(false);

    function destroy() {
        setProcessing(true);
        router.delete(route('customers.destroy', customer.id), {
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
                    {/* INFO CARD */}
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="mb-4 text-lg font-medium text-gray-900">Informations</h3>
                            <dl className="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                                <dt className="font-medium text-gray-500">Téléphone</dt>
                                <dd className="text-gray-900">{customer.phone}</dd>

                                <dt className="font-medium text-gray-500">Adresse e-mail</dt>
                                <dd className="text-gray-900">{customer.email ?? '—'}</dd>

                                <dt className="font-medium text-gray-500">Adresse</dt>
                                <dd className="text-gray-900">{customer.address ?? '—'}</dd>

                                {customer.notes && (
                                    <>
                                        <dt className="font-medium text-gray-500">Notes</dt>
                                        <dd className="text-gray-900">{customer.notes}</dd>
                                    </>
                                )}
                            </dl>
                        </div>
                    </div>

                    {/* TABS */}
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="border-b border-gray-200">
                            <nav className="-mb-px flex overflow-x-auto">
                                {TABS.map((tab) => (
                                    <button
                                        key={tab.key}
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
                        <div className="p-6">
                            {activeTab === 'vehicles' && (
                                <VehiclesTab vehicles={customer.vehicles} />
                            )}
                            {activeTab === 'history' && <HistoryTab />}
                            {activeTab === 'invoices' && (
                                <InvoicesTab invoices={customer.invoices} />
                            )}
                            {activeTab === 'appointments' && (
                                <AppointmentsTab appointments={customer.appointments} />
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
                    <h2 className="text-lg font-medium text-gray-900">Supprimer ce client ?</h2>
                    <p className="mt-1 text-sm text-gray-600">
                        <span className="font-medium">
                            {customer.first_name} {customer.last_name}
                        </span>{' '}
                        sera définitivement supprimé. Cette action est irréversible.
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
