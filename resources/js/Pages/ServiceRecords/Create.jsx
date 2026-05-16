import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create({ vehicle, serviceTypes }) {
    const today = new Date().toISOString().split('T')[0];

    const { data, setData, post, processing, errors } = useForm({
        service_type_ids: [],
        serviced_at: today,
        mileage_at_service: '',
        notes: '',
    });

    function toggleType(id) {
        setData(
            'service_type_ids',
            data.service_type_ids.includes(id)
                ? data.service_type_ids.filter((t) => t !== id)
                : [...data.service_type_ids, id],
        );
    }

    function submit(e) {
        e.preventDefault();
        post(route('vehicles.service-records.store', vehicle.id));
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Nouveau compte rendu —{' '}
                    <span className="font-normal text-gray-500">
                        {vehicle.make} {vehicle.model} ({vehicle.license_plate})
                    </span>
                </h2>
            }
        >
            <Head title="Nouveau compte rendu" />

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
                            <fieldset>
                                <legend className="text-sm font-medium text-gray-700">
                                    Types de service{' '}
                                    <span className="text-red-500" aria-hidden="true">
                                        *
                                    </span>
                                </legend>
                                <div className="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                                    {serviceTypes.map((type) => (
                                        <label
                                            key={type.id}
                                            className="flex cursor-pointer items-center gap-2 text-sm text-gray-700"
                                        >
                                            <Checkbox
                                                checked={data.service_type_ids.includes(type.id)}
                                                onChange={() => toggleType(type.id)}
                                            />
                                            {type.name}
                                        </label>
                                    ))}
                                </div>
                                <InputError message={errors.service_type_ids} className="mt-2" />
                            </fieldset>

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
                                    min={vehicle.mileage ?? 0}
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('mileage_at_service', e.target.value)}
                                />
                                {vehicle.mileage != null && (
                                    <p className="mt-1 text-xs text-gray-500">
                                        Minimum&nbsp;: {vehicle.mileage.toLocaleString('fr-MA')} km
                                    </p>
                                )}
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
                                    href={route('vehicles.show', vehicle.id)}
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
