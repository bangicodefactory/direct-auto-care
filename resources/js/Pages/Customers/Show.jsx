import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Show({ customer }) {

    return (
        <AuthenticatedLayout>
            <Head title="Client" />

            <div className="p-6 space-y-6">

                {/* INFO CLIENT */}
                <div>
                    <h1 className="text-3xl font-bold">
                        {customer.first_name} {customer.last_name}
                    </h1>

                    <p>{customer.email}</p>
                    <p>{customer.phone}</p>
                </div>

                {/* VEHICLES */}
                <div>
                    <h2 className="text-xl font-bold">Véhicules</h2>

                    {customer.vehicles?.map(v => (
                        <div key={v.id} className="border p-2">
                            {v.model}
                        </div>
                    ))}
                </div>

                {/* FACTURES */}
                <div>
                    <h2 className="text-xl font-bold">Factures</h2>

                    {customer.invoices?.map(i => (
                        <div key={i.id} className="border p-2">
                            #{i.id}
                        </div>
                    ))}
                </div>

                {/* APPOINTMENTS */}
                <div>
                    <h2 className="text-xl font-bold">Rendez-vous</h2>

                    {customer.appointments?.map(a => (
                        <div key={a.id} className="border p-2">
                            {a.date}
                        </div>
                    ))}
                </div>

                <Link
                    href={route('customers.index')}
                    className="text-blue-600"
                >
                    ← Retour
                </Link>

            </div>
        </AuthenticatedLayout>
    );
}