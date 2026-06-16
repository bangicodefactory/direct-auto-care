import Modal from '@/Components/Modal';
import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { AppointmentStatus } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

const STATUS_STYLES: Record<AppointmentStatus, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};

/** The appointment DTO projected by AppointmentsController@show. */
interface ShowAppointment {
    id: number;
    scheduled_at: string;
    duration: number;
    ends_at: string | null;
    status: AppointmentStatus;
    status_label: string;
    notes: string | null;
    customer: { id: number; name: string; phone: string; email: string | null };
    vehicle: { id: number; plate: string; make: string; model: string } | null;
    services: string[];
    created_by: string | null;
}

interface ShowCan {
    edit: boolean;
    cancel: boolean;
    complete: boolean;
    delete: boolean;
}

/** Props for the Appointments/Show page (AppointmentsController@show). */
interface AppointmentsShowProps {
    appointment: ShowAppointment;
    can: ShowCan;
}

function Field({ label, value }: { label: string; value: React.ReactNode }) {
    if (value === null || value === undefined || value === '') return null;
    return (
        <div>
            <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                {label}
            </p>
            <p className="mt-1 text-sm text-foreground">{value}</p>
        </div>
    );
}

function CancelButton({ appointmentId }: { appointmentId: number }) {
    const { post, processing } = useForm({});
    const [open, setOpen] = useState(false);

    return (
        <>
            <Button
                type="button"
                variant="outline"
                className="border-destructive text-destructive hover:bg-destructive/5"
                onClick={() => setOpen(true)}
                disabled={processing}
            >
                Annuler le RDV
            </Button>

            <Modal show={open} maxWidth="sm" onClose={() => setOpen(false)}>
                <div className="p-6">
                    <h3 className="text-base font-semibold text-foreground">
                        Confirmer l'annulation
                    </h3>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Cette action est irréversible. Le rendez-vous sera marqué comme annulé.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                            Retour
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            disabled={processing}
                            onClick={() => post(route('appointments.cancel', appointmentId))}
                        >
                            {processing ? 'Annulation…' : "Confirmer l'annulation"}
                        </Button>
                    </div>
                </div>
            </Modal>
        </>
    );
}

function CompleteButton({ appointmentId }: { appointmentId: number }) {
    const { post, processing } = useForm({});
    const [open, setOpen] = useState(false);

    return (
        <>
            <Button
                type="button"
                variant="outline"
                className="border-green-300 text-green-700 hover:bg-green-50"
                onClick={() => setOpen(true)}
                disabled={processing}
            >
                Marquer comme terminé
            </Button>

            <Modal show={open} maxWidth="sm" onClose={() => setOpen(false)}>
                <div className="p-6">
                    <h3 className="text-base font-semibold text-foreground">
                        Terminer le rendez-vous
                    </h3>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Le rendez-vous sera marqué comme terminé et vous serez redirigé vers la
                        création d'une fiche de service.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                            Retour
                        </Button>
                        <Button
                            type="button"
                            disabled={processing}
                            onClick={() => post(route('appointments.complete', appointmentId))}
                        >
                            {processing ? 'En cours…' : 'Terminer le rendez-vous'}
                        </Button>
                    </div>
                </div>
            </Modal>
        </>
    );
}

function DeleteButton({ appointmentId }: { appointmentId: number }) {
    const { delete: destroy, processing } = useForm({});

    function handleDelete() {
        if (!window.confirm('Supprimer définitivement ce rendez-vous ?')) return;
        destroy(route('appointments.destroy', appointmentId));
    }

    return (
        <Button type="button" variant="destructive" onClick={handleDelete} disabled={processing}>
            Supprimer
        </Button>
    );
}

export default function AppointmentsShow({ appointment, can }: AppointmentsShowProps) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <h2 className="text-xl font-semibold leading-tight text-foreground">
                            Rendez-vous #{appointment.id}
                        </h2>
                        <span
                            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${STATUS_STYLES[appointment.status] ?? 'bg-muted text-foreground'}`}
                        >
                            {appointment.status_label}
                        </span>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={route('appointments.index')}>← Calendrier</Link>
                        </Button>
                        {can.edit && appointment.status !== 'cancelled' && (
                            <Button variant="outline" asChild>
                                <Link href={route('appointments.edit', appointment.id)}>
                                    Modifier
                                </Link>
                            </Button>
                        )}
                        {can.complete && <CompleteButton appointmentId={appointment.id} />}
                        {can.cancel && appointment.status !== 'cancelled' && (
                            <CancelButton appointmentId={appointment.id} />
                        )}
                        {can.delete && <DeleteButton appointmentId={appointment.id} />}
                    </div>
                </div>
            }
        >
            <Head title={`Rendez-vous #${appointment.id}`} />

            <div className="py-8">
                <div className="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <div className="border-b border-border px-6 py-4">
                            <h3 className="text-sm font-semibold text-foreground">Détails</h3>
                        </div>
                        <div className="grid grid-cols-2 gap-6 p-6 sm:grid-cols-3">
                            <Field label="Date" value={appointment.scheduled_at} />
                            <Field label="Fin prévue" value={appointment.ends_at} />
                            <Field label="Durée" value={`${appointment.duration} min`} />
                            <Field label="Statut" value={appointment.status_label} />
                            {appointment.created_by && (
                                <Field label="Créé par" value={appointment.created_by} />
                            )}
                        </div>
                        {appointment.notes && (
                            <div className="border-t border-border px-6 py-4">
                                <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    Notes
                                </p>
                                <p className="mt-1 whitespace-pre-wrap text-sm text-foreground">
                                    {appointment.notes}
                                </p>
                            </div>
                        )}
                    </div>

                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <div className="border-b border-border px-6 py-4">
                            <h3 className="text-sm font-semibold text-foreground">Client</h3>
                        </div>
                        <div className="grid grid-cols-2 gap-6 p-6 sm:grid-cols-3">
                            <Field label="Nom" value={appointment.customer.name} />
                            <Field label="Téléphone" value={appointment.customer.phone} />
                            <Field label="E-mail" value={appointment.customer.email} />
                        </div>
                        <div className="border-t border-border px-6 py-3">
                            <Link
                                href={route('customers.show', appointment.customer.id)}
                                className="text-sm text-primary hover:underline"
                            >
                                Voir la fiche client →
                            </Link>
                        </div>
                    </div>

                    {appointment.vehicle && (
                        <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                            <div className="border-b border-border px-6 py-4">
                                <h3 className="text-sm font-semibold text-foreground">Véhicule</h3>
                            </div>
                            <div className="grid grid-cols-2 gap-6 p-6 sm:grid-cols-3">
                                <Field label="Immatriculation" value={appointment.vehicle.plate} />
                                <Field label="Marque" value={appointment.vehicle.make} />
                                <Field label="Modèle" value={appointment.vehicle.model} />
                            </div>
                            <div className="border-t border-border px-6 py-3">
                                <Link
                                    href={route('vehicles.show', appointment.vehicle.id)}
                                    className="text-sm text-primary hover:underline"
                                >
                                    Voir la fiche véhicule →
                                </Link>
                            </div>
                        </div>
                    )}

                    {appointment.services.length > 0 && (
                        <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                            <div className="border-b border-border px-6 py-4">
                                <h3 className="text-sm font-semibold text-foreground">
                                    Types de service
                                </h3>
                            </div>
                            <ul className="divide-y divide-border">
                                {appointment.services.map((name) => (
                                    <li key={name} className="px-6 py-3 text-sm text-foreground">
                                        {name}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
