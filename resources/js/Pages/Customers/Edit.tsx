import CustomerForm from '@/Components/CustomerForm';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Customer } from '@/types/models';
import { Head, useForm } from '@inertiajs/react';

interface EditProps {
    customer: Customer;
    can: { manage_fleet_notes: boolean };
}

export default function Edit({ customer, can }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        type: customer.type,
        company_name: customer.company_name ?? '',
        ice: customer.ice ?? '',
        if: customer.if ?? '',
        patente: customer.patente ?? '',
        ville: customer.ville ?? '',
        first_name: customer.first_name ?? '',
        last_name: customer.last_name ?? '',
        phone: customer.phone,
        email: customer.email ?? '',
        address: customer.address ?? '',
        notes: customer.notes ?? '',
        notify_whatsapp: customer.notify_whatsapp,
        notify_email: customer.notify_email,
        fleet_notes_enabled: customer.fleet_notes_enabled,
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        put(route('customers.update', customer.id));
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Modifier le client
                </h2>
            }
        >
            <Head title="Modifier le client" />

            <div className="py-8">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <CustomerForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            onSubmit={submit}
                            cancelHref={route('customers.show', customer.id)}
                            autoFocus={false}
                            showFleetNotesToggle={can.manage_fleet_notes}
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
