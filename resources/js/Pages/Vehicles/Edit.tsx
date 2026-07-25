import VehicleForm from '@/Components/VehicleForm';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { FuelType } from '@/types/models';
import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

interface EditVehicle {
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
}

interface EditProps {
    vehicle: EditVehicle;
}

export default function Edit({ vehicle }: EditProps) {
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

    function submit(e: FormEvent) {
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

            <div className="py-8">
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
