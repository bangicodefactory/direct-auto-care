import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ customers, filters }) {
    const [search, setSearch] = useState(filters?.search ?? '');

    function submitSearch(e) {
        e.preventDefault();
        router.get(
            route('customers.index'),
            { search, sort: filters?.sort, direction: filters?.direction },
            { preserveState: true, replace: true },
        );
    }

    function sortLink(field) {
        const isCurrent = filters?.sort === field;
        const direction = isCurrent && filters?.direction === 'asc' ? 'desc' : 'asc';
        return route('customers.index', { search: filters?.search ?? '', sort: field, direction });
    }

    function sortIcon(field) {
        if (filters?.sort !== field) return null;
        return filters?.direction === 'asc' ? ' ↑' : ' ↓';
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Clients</h2>
                    <Link
                        href={route('customers.create')}
                        className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        + Nouveau client
                    </Link>
                </div>
            }
        >
            <Head title="Clients" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        {/* SEARCH */}
                        <form onSubmit={submitSearch} className="flex gap-2 border-b p-4">
                            <TextInput
                                className="flex-1"
                                placeholder="Rechercher par nom, téléphone ou e-mail…"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                            <button
                                type="submit"
                                className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                            >
                                Rechercher
                            </button>
                            {filters?.search && (
                                <Link
                                    href={route('customers.index')}
                                    className="rounded-md border px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                                >
                                    Effacer
                                </Link>
                            )}
                        </form>

                        {/* TABLE */}
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        <Link href={sortLink('last_name')}>
                                            Nom{sortIcon('last_name')}
                                        </Link>
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        <Link href={sortLink('first_name')}>
                                            Prénom{sortIcon('first_name')}
                                        </Link>
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        <Link href={sortLink('email')}>
                                            E-mail{sortIcon('email')}
                                        </Link>
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Téléphone
                                    </th>
                                    <th className="px-6 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {customers.data.map((c) => (
                                    <tr key={c.id}>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                            {c.last_name}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {c.first_name}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {c.email ?? '—'}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {c.phone}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <Link
                                                href={route('customers.show', c.id)}
                                                className="mr-4 text-primary hover:text-primary/80"
                                            >
                                                Voir
                                            </Link>
                                            <Link
                                                href={route('customers.edit', c.id)}
                                                className="text-primary hover:text-primary/80"
                                            >
                                                Modifier
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                                {customers.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-6 py-8 text-center text-sm text-gray-500"
                                        >
                                            Aucun client trouvé.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>

                        {/* PAGINATION */}
                        {customers.links.length > 3 && (
                            <div className="flex items-center justify-between border-t border-gray-200 px-6 py-3">
                                {customers.links.map((link, i) =>
                                    link.url ? (
                                        <Link
                                            key={i}
                                            href={link.url}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className={`px-3 py-1 text-sm ${link.active ? 'font-bold text-primary' : 'text-gray-500 hover:text-gray-700'}`}
                                        />
                                    ) : (
                                        <span
                                            key={i}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className="px-3 py-1 text-sm text-gray-300"
                                        />
                                    ),
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
