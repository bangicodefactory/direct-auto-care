import VehicleForm from '@/Components/VehicleForm';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Customer } from '@/types/models';
import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

interface CreateProps {
    customer?: Pick<Customer, 'id' | 'display_name'> | null;
    customers?: Pick<Customer, 'id' | 'display_name' | 'phone'>[] | null;
}

export default function Create({ customer = null, customers = null }: CreateProps) {
    const isStandalone = customers !== null;

    const { data, setData, post, processing, errors } = useForm({
        customer_id: customer?.id ?? '',
        make: '',
        model: '',
        year: new Date().getFullYear(),
        license_plate: '',
        vin: '',
        mileage: 0,
        fuel_type: '',
        color: '',
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        if (isStandalone) {
            post(route('vehicles.store'));
        } else {
            post(route('customers.vehicles.store', customer!.id));
        }
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Nouveau véhicule
                    {customer && (
                        <>
                            {' '}
                            —{' '}
                            <span className="font-normal text-gray-500">
                                {customer.display_name}
                            </span>
                        </>
                    )}
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
                            cancelHref={
                                isStandalone
                                    ? route('vehicles.index')
                                    : route('customers.show', customer!.id)
                            }
                            customers={isStandalone ? customers : null}
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
