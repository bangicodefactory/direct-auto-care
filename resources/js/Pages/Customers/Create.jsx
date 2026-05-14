import CustomerForm from '@/Components/CustomerForm';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        first_name: '',
        last_name: '',
        phone: '',
        email: '',
        address: '',
        notes: '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('customers.store'));
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Nouveau client
                </h2>
            }
        >
            <Head title="Nouveau client" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <CustomerForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            onSubmit={submit}
                            cancelHref={route('customers.index')}
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
