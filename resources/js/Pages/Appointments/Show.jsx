import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

const STATUS_STYLES = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};

function Field({ label, value }) {
    if (value === null || value === undefined || value === '') return null;
    return (
        <div>
            <p className="text-xs font-medium uppercase tracking-wide text-gray-500">{label}</p>
            <p className="mt-1 text-sm text-gray-900">{value}</p>
        </div>
    );
}

function CancelButton({ appointmentId }) {
    const { post, processing } = useForm({});

    function handleCancel() {
        if (!window.confirm("Confirmer l'annulation de ce rendez-vous ?")) return;
        post(route('appointments.cancel', appointmentId));
    }

    return (
        <button
            type="button"
            onClick={handleCancel}
            disabled={processing}
            className="rounded-md border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 disabled:opacity-50"
        >
            Annuler le RDV
        </button>
    );
}

function DeleteButton({ appointmentId }) {
    const { delete: destroy, processing } = useForm({});

    function handleDelete() {
        if (!window.confirm('Supprimer définitivement ce rendez-vous ?')) return;
        destroy(route('appointments.destroy', appointmentId));
    }

    return (
        <button
            type="button"
            onClick={handleDelete}
            disabled={processing}
            className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-100 disabled:opacity-50"
        >
            Supprimer
        </button>
    );
}

export default function AppointmentsShow({ appointment, can }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            Rendez-vous #{appointment.id}
                        </h2>
                        <span
                            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${STATUS_STYLES[appointment.status] ?? 'bg-gray-100 text-gray-700'}`}
                        >
                            {appointment.status_label}
                        </span>
                    </div>
                    <div className="flex items-center gap-2">
                        <Link
                            href={route('appointments.index')}
                            className="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            ← Calendrier
                        </Link>
                        {can.edit && appointment.status !== 'cancelled' && (
                            <Link
                                href={route('appointments.edit', appointment.id)}
                                className="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Modifier
                            </Link>
                        )}
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
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="border-b border-gray-100 px-6 py-4">
                            <h3 className="text-sm font-semibold text-gray-700">Détails</h3>
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
                            <div className="border-t border-gray-100 px-6 py-4">
                                <p className="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Notes
                                </p>
                                <p className="mt-1 whitespace-pre-wrap text-sm text-gray-900">
                                    {appointment.notes}
                                </p>
                            </div>
                        )}
                    </div>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div className="border-b border-gray-100 px-6 py-4">
                            <h3 className="text-sm font-semibold text-gray-700">Client</h3>
                        </div>
                        <div className="grid grid-cols-2 gap-6 p-6 sm:grid-cols-3">
                            <Field label="Nom" value={appointment.customer.name} />
                            <Field label="Téléphone" value={appointment.customer.phone} />
                            <Field label="E-mail" value={appointment.customer.email} />
                        </div>
                        <div className="border-t border-gray-100 px-6 py-3">
                            <Link
                                href={route('customers.show', appointment.customer.id)}
                                className="text-sm text-primary hover:underline"
                            >
                                Voir la fiche client →
                            </Link>
                        </div>
                    </div>

                    {appointment.vehicle && (
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="border-b border-gray-100 px-6 py-4">
                                <h3 className="text-sm font-semibold text-gray-700">Véhicule</h3>
                            </div>
                            <div className="grid grid-cols-2 gap-6 p-6 sm:grid-cols-3">
                                <Field label="Immatriculation" value={appointment.vehicle.plate} />
                                <Field label="Marque" value={appointment.vehicle.make} />
                                <Field label="Modèle" value={appointment.vehicle.model} />
                            </div>
                            <div className="border-t border-gray-100 px-6 py-3">
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
                        <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                            <div className="border-b border-gray-100 px-6 py-4">
                                <h3 className="text-sm font-semibold text-gray-700">
                                    Types de service
                                </h3>
                            </div>
                            <ul className="divide-y divide-gray-100">
                                {appointment.services.map((name, i) => (
                                    <li key={i} className="px-6 py-3 text-sm text-gray-700">
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
