import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, Link } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ customers, filters }) {
    const [search, setSearch] = useState(filters?.search || '');

    const submitSearch = (e) => {
        e.preventDefault();

        router.get(
            route('customers.index'),
            {
                search,
                sort: filters?.sort,
                direction: filters?.direction,
            },
            {
                preserveState: true,
                replace: true,
            },
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title="Clients" />

            <div className="p-6 space-y-4">
                {/* HEADER */}
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold">Clients</h1>

                    <Link
                        href={route('customers.create')}
                        className="bg-blue-600 text-white px-4 py-2 rounded"
                    >
                        + Nouveau client
                    </Link>
                </div>

                {/* SEARCH */}
                <form onSubmit={submitSearch} className="flex gap-2">
                    <input
                        className="border p-2 w-full"
                        placeholder="Search name, email, phone..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />

                    <button className="bg-black text-white px-4">Search</button>
                </form>

                {/* TABLE */}
                <table className="w-full border">
                    <thead>
                        <tr>
                            <th>Prénom</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        {customers.data.map((c) => (
                            <tr key={c.id} className="border-t">
                                <td>{c.first_name}</td>
                                <td>{c.last_name}</td>
                                <td>{c.email}</td>
                                <td>{c.phone}</td>

                                <td className="flex gap-2">
                                    <Link href={route('customers.show', c.id)}>Voir</Link>

                                    <Link href={route('customers.edit', c.id)}>Modifier</Link>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                {/* PAGINATION */}
                <div className="flex gap-2 mt-4">
                    {customers.links.map((link, i) => (
                        <Link
                            key={i}
                            href={link.url || ''}
                            className={`px-2 py-1 border ${
                                link.active ? 'bg-black text-white' : ''
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
