import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Create() {

    const [form, setForm] = useState({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();

        router.post(route('customers.store'), form);
    };

    return (
        <AuthenticatedLayout>
            <Head title="Nouveau client" />

            <div className="p-6 max-w-xl">

                <h1 className="text-2xl font-bold mb-4">
                    Créer un client
                </h1>

                <form onSubmit={handleSubmit} className="space-y-3">

                    <input
                        className="border p-2 w-full"
                        placeholder="Prénom"
                        value={form.first_name}
                        onChange={(e) =>
                            setForm({ ...form, first_name: e.target.value })
                        }
                    />

                    <input
                        className="border p-2 w-full"
                        placeholder="Nom"
                        value={form.last_name}
                        onChange={(e) =>
                            setForm({ ...form, last_name: e.target.value })
                        }
                    />

                    <input
                        className="border p-2 w-full"
                        placeholder="Email"
                        value={form.email}
                        onChange={(e) =>
                            setForm({ ...form, email: e.target.value })
                        }
                    />

                    <input
                        className="border p-2 w-full"
                        placeholder="Téléphone"
                        value={form.phone}
                        onChange={(e) =>
                            setForm({ ...form, phone: e.target.value })
                        }
                    />

                    <button className="bg-green-600 text-white px-4 py-2">
                        Enregistrer
                    </button>

                </form>
            </div>
        </AuthenticatedLayout>
    );
}