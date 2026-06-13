import Modal from '@/Components/Modal';
import { Button } from '@/Components/ui/button';
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
    return <p className="text-sm text-muted-foreground">{message}</p>;
}

function VehiclesTab({ vehicles }) {
    if (!vehicles?.length) return <EmptyState message="Aucun véhicule enregistré." />;
    return (
        <ul className="divide-y divide-border">
            {vehicles.map((v) => (
                <li key={v.id} className="py-3 text-sm text-foreground">
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
        <ul className="divide-y divide-border">
            {invoices.map((inv) => (
                <li key={inv.id} className="py-3 text-sm text-foreground">
                    Facture #{inv.id}
                </li>
            ))}
        </ul>
    );
}

function AppointmentsTab({ appointments }) {
    if (!appointments?.length) return <EmptyState message="Aucun rendez-vous." />;
    return (
        <ul className="divide-y divide-border">
            {appointments.map((a) => (
                <li key={a.id} className="py-3 text-sm text-foreground">
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
                    <h2 className="text-xl font-semibold leading-tight text-foreground">
                        {customer.first_name} {customer.last_name}
                    </h2>
                    <div className="flex gap-3">
                        <Button asChild>
                            <Link href={route('customers.edit', customer.id)}>Modifier</Link>
                        </Button>
                        {can.delete && (
                            <Button
                                variant="destructive"
                                onClick={() => setConfirmingDeletion(true)}
                            >
                                Supprimer
                            </Button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`${customer.first_name} ${customer.last_name}`} />

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <div className="p-6">
                            <h3 className="mb-4 text-lg font-medium text-foreground">
                                Informations
                            </h3>
                            <dl className="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                                <dt className="font-medium text-muted-foreground">Type</dt>
                                <dd className="text-foreground">
                                    {customer.type === 'company' ? 'Société' : 'Particulier'}
                                </dd>

                                {customer.type === 'company' && (
                                    <>
                                        <dt className="font-medium text-muted-foreground">ICE</dt>
                                        <dd className="text-foreground">{customer.ice ?? '—'}</dd>
                                    </>
                                )}

                                <dt className="font-medium text-muted-foreground">Téléphone</dt>
                                <dd className="text-foreground">{customer.phone}</dd>

                                <dt className="font-medium text-muted-foreground">
                                    Adresse e-mail
                                </dt>
                                <dd className="text-foreground">{customer.email ?? '—'}</dd>

                                <dt className="font-medium text-muted-foreground">Adresse</dt>
                                <dd className="text-foreground">{customer.address ?? '—'}</dd>

                                {customer.notes && (
                                    <>
                                        <dt className="font-medium text-muted-foreground">Notes</dt>
                                        <dd className="text-foreground">{customer.notes}</dd>
                                    </>
                                )}
                            </dl>
                        </div>
                    </div>

                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <div className="border-b border-border">
                            <nav
                                className="-mb-px flex overflow-x-auto"
                                role="tablist"
                                aria-label="Sections du client"
                            >
                                {TABS.map((tab) => (
                                    <button
                                        key={tab.key}
                                        type="button"
                                        role="tab"
                                        aria-selected={activeTab === tab.key}
                                        aria-controls={`tabpanel-${tab.key}`}
                                        onClick={() => setActiveTab(tab.key)}
                                        className={`whitespace-nowrap border-b-2 px-6 py-4 text-sm font-medium transition-colors ${
                                            activeTab === tab.key
                                                ? 'border-primary text-primary'
                                                : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground'
                                        }`}
                                    >
                                        {tab.label}
                                    </button>
                                ))}
                            </nav>
                        </div>
                        <div role="tabpanel" id={`tabpanel-${activeTab}`} className="p-6">
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
                        className="text-sm text-muted-foreground hover:text-foreground"
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
                    <h2 className="text-lg font-medium text-foreground">Supprimer ce client ?</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        <span className="font-medium">
                            {customer.first_name} {customer.last_name}
                        </span>{' '}
                        sera définitivement supprimé. Cette action est irréversible.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <Button variant="secondary" onClick={() => setConfirmingDeletion(false)}>
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
