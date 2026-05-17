import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Create() {
    return (
        <AuthenticatedLayout>
            <Head title="Nouveau document" />
            <div className="p-6">
                <p className="text-gray-500">Formulaire de création — à implémenter.</p>
            </div>
        </AuthenticatedLayout>
    );
}
