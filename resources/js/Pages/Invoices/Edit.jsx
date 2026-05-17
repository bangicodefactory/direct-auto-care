import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Edit({ invoice }) {
    return (
        <AuthenticatedLayout>
            <Head title={`Modifier ${invoice.number}`} />
            <div className="p-6">
                <p className="text-gray-500">Formulaire de modification — à implémenter.</p>
            </div>
        </AuthenticatedLayout>
    );
}
