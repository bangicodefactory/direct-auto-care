import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import ServiceTypeMultiCheck from '@/Components/ServiceTypeMultiCheck';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Edit({ record, serviceTypes }) {
    const today = new Date().toISOString().split('T')[0];
    const { make, model, license_plate, id: vehicleId } = record.vehicle;

    const { data, setData, patch, processing, errors } = useForm({
        service_type_ids: record.service_type_ids,
        serviced_at: record.serviced_at,
        mileage_at_service: record.mileage_at_service ?? '',
        notes: record.notes ?? '',
    });

    function submit(e) {
        e.preventDefault();
        patch(route('service-records.update', record.id));
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Modifier le compte rendu —{' '}
                    <span className="font-normal text-gray-500">
                        {make} {model} ({license_plate})
                    </span>
                </h2>
            }
        >
            <Head title="Modifier le compte rendu" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <form onSubmit={submit} className="space-y-6 p-6">
                            {/* Date */}
                            <div>
                                <InputLabel htmlFor="serviced_at" value="Date du service" />
                                <TextInput
                                    id="serviced_at"
                                    type="date"
                                    value={data.serviced_at}
                                    max={today}
                                    isFocused
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('serviced_at', e.target.value)}
                                />
                                <InputError message={errors.serviced_at} className="mt-2" />
                            </div>

                            {/* Service types */}
                            <ServiceTypeMultiCheck
                                serviceTypes={serviceTypes}
                                selected={data.service_type_ids}
                                onChange={(ids) => setData('service_type_ids', ids)}
                                error={errors.service_type_ids}
                            />

                            {/* Mileage */}
                            <div>
                                <InputLabel
                                    htmlFor="mileage_at_service"
                                    value="Kilométrage au moment du service (km)"
                                />
                                <TextInput
                                    id="mileage_at_service"
                                    type="number"
                                    value={data.mileage_at_service}
                                    min={0}
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('mileage_at_service', e.target.value)}
                                />
                                <InputError message={errors.mileage_at_service} className="mt-2" />
                            </div>

                            {/* Notes */}
                            <div>
                                <InputLabel htmlFor="notes" value="Notes" />
                                <textarea
                                    id="notes"
                                    value={data.notes}
                                    rows={4}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                                    onChange={(e) => setData('notes', e.target.value)}
                                />
                                <InputError message={errors.notes} className="mt-2" />
                            </div>

                            <div className="flex items-center justify-end gap-4">
                                <Link
                                    href={route('vehicles.show', vehicleId)}
                                    className="text-sm text-gray-600 hover:text-gray-900"
                                >
                                    Annuler
                                </Link>
                                <PrimaryButton disabled={processing}>Enregistrer</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
