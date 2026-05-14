import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Edit({ customer }) {

    const [form, setForm] = useState({
        first_name: customer.first_name,
        last_name: customer.last_name,
        email: customer.email,
        phone: customer.phone,
    });

    const handleSubmit = (e) => {
        e.preventDefault();

        router.put(route('customers.update', customer.id), form);
    };

    return (
        <AuthenticatedLayout>
            <Head title="Modifier client" />

            <div className="p-6 max-w-xl">

                <h1 className="text-2xl font-bold mb-4">
                    Modifier client
                </h1>

                <form onSubmit={handleSubmit} className="space-y-3">

                    <input
                        className="border p-2 w-full"
                        value={form.first_name}
                        onChange={(e) =>
                            setForm({ ...form, first_name: e.target.value })
                        }
                    />

                    <input
                        className="border p-2 w-full"
                        value={form.last_name}
                        onChange={(e) =>
                            setForm({ ...form, last_name: e.target.value })
                        }
                    />

                    <input
                        className="border p-2 w-full"
                        value={form.email}
                        onChange={(e) =>
                            setForm({ ...form, email: e.target.value })
                        }
                    />

                    <input
                        className="border p-2 w-full"
                        value={form.phone}
                        onChange={(e) =>
                            setForm({ ...form, phone: e.target.value })
                        }
                    />

                    <button className="bg-blue-600 text-white px-4 py-2">
                        Modifier
                    </button>

                </form>
            </div>
        </AuthenticatedLayout>
    );
}