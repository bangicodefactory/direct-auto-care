import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Create() {
    return (
        <AuthenticatedLayout>
            <Head title="Nouvelle fiche de service" />
            <div className="py-12">
                <div className="mx-auto max-w-3xl sm:px-6 lg:px-8">
                    <p className="text-gray-500">Formulaire à implémenter (IST-35 backend stub).</p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
