import CustomerForm from '@/Components/CustomerForm';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Edit({ customer }) {
    const { data, setData, put, processing, errors } = useForm({
        first_name: customer.first_name,
        last_name: customer.last_name,
        phone: customer.phone,
        email: customer.email ?? '',
        address: customer.address ?? '',
        notes: customer.notes ?? '',
        notify_whatsapp: customer.notify_whatsapp,
        notify_email: customer.notify_email,
    });

    function submit(e) {
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

            <div className="py-12">
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
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
