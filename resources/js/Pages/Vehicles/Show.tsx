import Modal from '@/Components/Modal';
import { Button } from '@/Components/ui/button';
import { FUEL_LABELS } from '@/constants/fuelTypes';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { FuelType } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

interface ServiceRecordRow {
    id: number;
    serviced_at: string;
    mileage_at_service: number | null;
    notes: string | null;
    service_types: string[];
    can_edit: boolean;
    can_delete: boolean;
}

interface AppointmentRow {
    id: number;
    created_at: string;
}

interface ShowVehicle {
    id: number;
    make: string;
    model: string;
    year: number;
    license_plate: string;
    vin: string | null;
    mileage: number;
    fuel_type: FuelType;
    color: string | null;
    customer: {
        id: number;
        display_name: string;
    };
    service_records: ServiceRecordRow[];
    appointments: AppointmentRow[];
}

interface ShowProps {
    vehicle: ShowVehicle;
    can: {
        delete: boolean;
        create_service_record: boolean;
    };
}

const TABS = [
    { key: 'history', label: 'Historique de service' },
    { key: 'appointments', label: 'Rendez-vous' },
    { key: 'documents', label: 'Documents' },
];

function EmptyState({ message }: { message: string }) {
    return <p className="text-sm text-muted-foreground">{message}</p>;
}

function ServiceHistoryTab({
    records,
    onDeleteRecord,
}: {
    records: ServiceRecordRow[];
    onDeleteRecord: (record: ServiceRecordRow) => void;
}) {
    if (!records.length) return <EmptyState message="Aucun historique de service enregistré." />;

    return (
        <ul className="divide-y divide-border">
            {records.map((r) => (
                <li key={r.id} className="py-4">
                    <div className="flex items-start justify-between gap-4">
                        <div className="min-w-0 space-y-1">
                            <p className="text-sm font-semibold text-foreground">{r.serviced_at}</p>

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
                                <p className="text-sm text-muted-foreground">
                                    {r.mileage_at_service.toLocaleString('fr-MA')} km
                                </p>
                            )}

                            {r.notes && (
                                <p className="line-clamp-2 text-sm text-muted-foreground">
                                    {r.notes}
                                </p>
                            )}
                        </div>

                        <div className="flex shrink-0 gap-3">
                            <Link
                                href={route('service-records.show', r.id)}
                                className="text-sm text-muted-foreground hover:underline"
                            >
                                Voir
                            </Link>
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
                                    className="text-sm text-destructive hover:text-destructive/80"
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

function AppointmentsTab({ appointments }: { appointments: AppointmentRow[] }) {
    if (!appointments.length) return <EmptyState message="Aucun rendez-vous enregistré." />;
    return (
        <ul className="divide-y divide-border">
            {appointments.map((a) => (
                <li key={a.id} className="py-3 text-sm text-foreground">
                    Rendez-vous du {a.created_at}
                </li>
            ))}
        </ul>
    );
}

function DocumentsTab() {
    return <EmptyState message="Module documents à venir." />;
}

export default function Show({ vehicle, can }: ShowProps) {
    const [activeTab, setActiveTab] = useState('history');
    const [confirmingDeletion, setConfirmingDeletion] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [deletingRecord, setDeletingRecord] = useState<ServiceRecordRow | null>(null);
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
        router.delete(route('service-records.destroy', deletingRecord!.id), {
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
                    <h2 className="text-xl font-semibold leading-tight text-foreground">
                        {vehicle.make} {vehicle.model}{' '}
                        <span className="font-normal text-muted-foreground">({vehicle.year})</span>
                    </h2>
                    <div className="flex gap-2">
                        {can.create_service_record && (
                            <Button asChild>
                                <Link href={route('vehicles.service-records.create', vehicle.id)}>
                                    Nouveau compte rendu
                                </Link>
                            </Button>
                        )}
                        <Button variant="outline" asChild>
                            <Link href={route('vehicles.edit', vehicle.id)}>Modifier</Link>
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
            <Head title={`${vehicle.make} ${vehicle.model}`} />

            <div className="py-12">
                <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                    {/* INFO CARD */}
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <div className="border-b border-border p-6">
                            <h3 className="text-base font-semibold text-foreground">
                                Informations
                            </h3>
                        </div>
                        <dl className="divide-y divide-border p-6">
                            <div className="grid grid-cols-2 gap-4 py-3">
                                <dt className="text-sm font-medium text-muted-foreground">
                                    Plaque
                                </dt>
                                <dd className="font-mono text-sm text-foreground">
                                    {vehicle.license_plate}
                                </dd>
                            </div>
                            <div className="grid grid-cols-2 gap-4 py-3">
                                <dt className="text-sm font-medium text-muted-foreground">
                                    Marque / Modèle
                                </dt>
                                <dd className="text-sm text-foreground">
                                    {vehicle.make} {vehicle.model}
                                </dd>
                            </div>
                            <div className="grid grid-cols-2 gap-4 py-3">
                                <dt className="text-sm font-medium text-muted-foreground">Année</dt>
                                <dd className="text-sm text-foreground">{vehicle.year}</dd>
                            </div>
                            <div className="grid grid-cols-2 gap-4 py-3">
                                <dt className="text-sm font-medium text-muted-foreground">
                                    Carburant
                                </dt>
                                <dd className="text-sm text-foreground">
                                    {FUEL_LABELS[vehicle.fuel_type] ?? vehicle.fuel_type}
                                </dd>
                            </div>
                            <div className="grid grid-cols-2 gap-4 py-3">
                                <dt className="text-sm font-medium text-muted-foreground">
                                    Kilométrage
                                </dt>
                                <dd className="text-sm text-foreground">
                                    {vehicle.mileage.toLocaleString('fr-MA')} km
                                </dd>
                            </div>
                            {vehicle.color && (
                                <div className="grid grid-cols-2 gap-4 py-3">
                                    <dt className="text-sm font-medium text-muted-foreground">
                                        Couleur
                                    </dt>
                                    <dd className="text-sm text-foreground">{vehicle.color}</dd>
                                </div>
                            )}
                            {vehicle.vin && (
                                <div className="grid grid-cols-2 gap-4 py-3">
                                    <dt className="text-sm font-medium text-muted-foreground">
                                        Numéro de châssis
                                    </dt>
                                    <dd className="font-mono text-sm text-foreground">
                                        {vehicle.vin}
                                    </dd>
                                </div>
                            )}
                            <div className="grid grid-cols-2 gap-4 py-3">
                                <dt className="text-sm font-medium text-muted-foreground">
                                    Propriétaire
                                </dt>
                                <dd className="text-sm">
                                    <Link
                                        href={route('customers.show', vehicle.customer.id)}
                                        className="text-primary hover:underline"
                                    >
                                        {vehicle.customer.display_name}
                                    </Link>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    {/* TABS */}
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <div className="border-b border-border">
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
                                                : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground'
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
                    <h2 className="text-lg font-medium text-foreground">Supprimer ce véhicule ?</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        <span className="font-medium">
                            {vehicle.make} {vehicle.model} — {vehicle.license_plate}
                        </span>{' '}
                        sera supprimé. Cette action est irréversible.
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

            <Modal show={!!deletingRecord} maxWidth="sm" onClose={() => setDeletingRecord(null)}>
                <div className="p-6">
                    <h2 className="text-lg font-medium text-foreground">
                        Supprimer ce compte rendu ?
                    </h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Le compte rendu du{' '}
                        <span className="font-medium">{deletingRecord?.serviced_at}</span> sera
                        supprimé définitivement.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <Button variant="secondary" onClick={() => setDeletingRecord(null)}>
                            Annuler
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={destroyRecord}
                            disabled={processingRecord}
                        >
                            Supprimer
                        </Button>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
