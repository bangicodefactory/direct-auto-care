import VehicleForm from '@/Components/VehicleForm';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Create({ customer }) {
    const { data, setData, post, processing, errors } = useForm({
        customer_id: customer.id,
        make: '',
        model: '',
        year: new Date().getFullYear(),
        license_plate: '',
        vin: '',
        mileage: 0,
        fuel_type: '',
        color: '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('customers.vehicles.store', customer.id));
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Nouveau véhicule —{' '}
                    <span className="font-normal text-gray-500">
                        {customer.first_name} {customer.last_name}
                    </span>
                </h2>
            }
        >
            <Head title="Nouveau véhicule" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <VehicleForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            onSubmit={submit}
                            cancelHref={route('customers.show', customer.id)}
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
