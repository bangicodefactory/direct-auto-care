import VehicleForm from '@/Components/VehicleForm';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Edit({ vehicle }) {
    const { data, setData, put, processing, errors } = useForm({
        make: vehicle.make,
        model: vehicle.model,
        year: vehicle.year,
        license_plate: vehicle.license_plate,
        vin: vehicle.vin ?? '',
        mileage: vehicle.mileage,
        fuel_type: vehicle.fuel_type,
        color: vehicle.color ?? '',
    });

    function submit(e) {
        e.preventDefault();
        put(route('vehicles.update', vehicle.id));
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Modifier le véhicule —{' '}
                    <span className="font-normal text-gray-500">{vehicle.license_plate}</span>
                </h2>
            }
        >
            <Head title="Modifier le véhicule" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <VehicleForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            onSubmit={submit}
                            cancelHref={route('vehicles.show', vehicle.id)}
                            autoFocus={false}
                            minMileage={vehicle.mileage ?? 0}
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
