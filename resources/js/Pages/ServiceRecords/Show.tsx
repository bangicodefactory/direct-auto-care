import Modal from '@/Components/Modal';
import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { ServiceRecord, Vehicle } from '@/types/models';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

interface ShowProps {
    record: Pick<ServiceRecord, 'id' | 'mileage_at_service' | 'notes'> & {
        serviced_at: string;
        service_types: string[];
        vehicle: Pick<Vehicle, 'id' | 'make' | 'model' | 'license_plate'>;
    };
    can: {
        edit: boolean;
        delete: boolean;
    };
}

export default function Show({ record, can }: ShowProps) {
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
                    <h2 className="text-xl font-semibold leading-tight text-foreground">
                        Compte rendu du {record.serviced_at}
                    </h2>
                    <div className="flex gap-2">
                        {can.edit && (
                            <Button variant="outline" asChild>
                                <Link href={route('service-records.edit', record.id)}>
                                    Modifier
                                </Link>
                            </Button>
                        )}
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
            <Head title="Compte rendu de service" />

            <div className="py-8">
                <div className="mx-auto max-w-2xl space-y-6 sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <div className="border-b border-border p-6">
                            <h3 className="text-base font-semibold text-foreground">
                                Informations
                            </h3>
                        </div>
                        <dl className="divide-y divide-border p-6">
                            <div className="grid grid-cols-2 gap-4 py-3">
                                <dt className="text-sm font-medium text-muted-foreground">
                                    Véhicule
                                </dt>
                                <dd className="text-sm text-foreground">
                                    <Link
                                        href={route('vehicles.show', record.vehicle.id)}
                                        className="inline-flex min-h-[44px] items-center text-primary hover:underline"
                                    >
                                        {record.vehicle.make} {record.vehicle.model}{' '}
                                        <span className="font-mono">
                                            ({record.vehicle.license_plate})
                                        </span>
                                    </Link>
                                </dd>
                            </div>
                            <div className="grid grid-cols-2 gap-4 py-3">
                                <dt className="text-sm font-medium text-muted-foreground">
                                    Date du service
                                </dt>
                                <dd className="text-sm text-foreground">{record.serviced_at}</dd>
                            </div>
                            <div className="grid grid-cols-2 gap-4 py-3">
                                <dt className="text-sm font-medium text-muted-foreground">
                                    Types de service
                                </dt>
                                <dd className="text-sm text-foreground">
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
                                        <span className="text-muted-foreground">—</span>
                                    )}
                                </dd>
                            </div>
                            {record.mileage_at_service != null && (
                                <div className="grid grid-cols-2 gap-4 py-3">
                                    <dt className="text-sm font-medium text-muted-foreground">
                                        Kilométrage
                                    </dt>
                                    <dd className="text-sm tabular-nums text-foreground">
                                        {record.mileage_at_service.toLocaleString('fr-MA')} km
                                    </dd>
                                </div>
                            )}
                            {record.notes && (
                                <div className="grid grid-cols-2 gap-4 py-3">
                                    <dt className="text-sm font-medium text-muted-foreground">
                                        Notes
                                    </dt>
                                    <dd className="whitespace-pre-wrap text-sm text-foreground">
                                        {record.notes}
                                    </dd>
                                </div>
                            )}
                        </dl>
                    </div>

                    <Link
                        href={route('vehicles.show', record.vehicle.id)}
                        className="text-sm text-muted-foreground hover:text-foreground"
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
                    <h2 className="text-lg font-medium text-foreground">
                        Supprimer ce compte rendu ?
                    </h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Le compte rendu du <span className="font-medium">{record.serviced_at}</span>{' '}
                        sera supprimé définitivement.
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
