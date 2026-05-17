import CustomerCombobox from '@/Components/CustomerCombobox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import ServiceTypeMultiCheck from '@/Components/ServiceTypeMultiCheck';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo } from 'react';

export default function AppointmentsEdit({ appointment, customers, vehicles, serviceTypes, statuses }) {
    const { data, setData, put, processing, errors } = useForm({
        customer_id: appointment.customer_id ?? '',
        vehicle_id: appointment.vehicle_id ?? '',
        scheduled_at: appointment.scheduled_at ?? '',
        duration: appointment.duration ?? 60,
        status: appointment.status ?? '',
        service_type_ids: appointment.service_type_ids ?? [],
        notes: appointment.notes ?? '',
    });

    const filteredVehicles = useMemo(
        () => vehicles.filter((v) => String(v.customer_id) === String(data.customer_id)),
        [vehicles, data.customer_id],
    );

    function submit(e) {
        e.preventDefault();
        put(route('appointments.update', appointment.id));
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Modifier le rendez-vous #{appointment.id}
                    </h2>
                    <Link
                        href={route('appointments.show', appointment.id)}
                        className="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        ← Retour
                    </Link>
                </div>
            }
        >
            <Head title={`Modifier RDV #${appointment.id}`} />

            <div className="py-8">
                <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="divide-y divide-gray-100">
                            <div className="space-y-5 p-6">
                                <div>
                                    <InputLabel value="Client *" />
                                    <CustomerCombobox
                                        customers={customers}
                                        value={data.customer_id}
                                        onChange={(id) =>
                                            setData((prev) => ({
                                                ...prev,
                                                customer_id: id,
                                                vehicle_id: '',
                                            }))
                                        }
                                        error={errors.customer_id}
                                    />
                                </div>

                                <div>
                                    <InputLabel htmlFor="vehicle_id" value="Véhicule" />
                                    <select
                                        id="vehicle_id"
                                        value={data.vehicle_id}
                                        onChange={(e) => setData('vehicle_id', e.target.value)}
                                        disabled={!data.customer_id}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm disabled:opacity-50"
                                    >
                                        <option value="">— Aucun —</option>
                                        {filteredVehicles.map((v) => (
                                            <option key={v.id} value={v.id}>
                                                {v.license_plate} {v.make} {v.model}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.vehicle_id} className="mt-1" />
                                </div>

                                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                    <div>
                                        <InputLabel htmlFor="scheduled_at" value="Date et heure *" />
                                        <TextInput
                                            id="scheduled_at"
                                            type="datetime-local"
                                            value={data.scheduled_at}
                                            onChange={(e) => setData('scheduled_at', e.target.value)}
                                            className="mt-1 block w-full"
                                        />
                                        <InputError message={errors.scheduled_at} className="mt-1" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="duration" value="Durée (minutes) *" />
                                        <TextInput
                                            id="duration"
                                            type="number"
                                            min={15}
                                            max={480}
                                            step={15}
                                            value={data.duration}
                                            onChange={(e) => setData('duration', e.target.value)}
                                            className="mt-1 block w-full"
                                        />
                                        <InputError message={errors.duration} className="mt-1" />
                                    </div>
                                </div>

                                <div>
                                    <InputLabel htmlFor="status" value="Statut *" />
                                    <select
                                        id="status"
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    >
                                        {statuses.map((s) => (
                                            <option key={s.value} value={s.value}>
                                                {s.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.status} className="mt-1" />
                                </div>

                                <ServiceTypeMultiCheck
                                    serviceTypes={serviceTypes}
                                    selected={data.service_type_ids}
                                    onChange={(ids) => setData('service_type_ids', ids)}
                                    error={errors.service_type_ids}
                                    label="Types de service"
                                />

                                <div>
                                    <InputLabel htmlFor="notes" value="Notes" />
                                    <textarea
                                        id="notes"
                                        rows={4}
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                    />
                                    <InputError message={errors.notes} className="mt-1" />
                                </div>
                            </div>

                            <div className="flex justify-end gap-3 px-6 py-4">
                                <Link
                                    href={route('appointments.show', appointment.id)}
                                    className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Annuler
                                </Link>
                                <PrimaryButton disabled={processing}>
                                    {processing ? 'Enregistrement…' : 'Enregistrer les modifications'}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
