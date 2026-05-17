import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Index() {
    return (
        <AuthenticatedLayout>
            <Head title="Factures & Devis" />
            <div className="p-6">
                <p className="text-gray-500">Liste des factures et devis — à implémenter.</p>
            </div>
        </AuthenticatedLayout>
    );
}
