import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Show({ invoice }) {
    return (
        <AuthenticatedLayout>
            <Head title={invoice.number} />
            <div className="p-6">
                <p className="text-gray-500">Détail du document — à implémenter.</p>
            </div>
        </AuthenticatedLayout>
    );
}
