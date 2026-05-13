import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Create() {
    return (
        <AuthenticatedLayout>
            <Head title="Nouveau client" />
        </AuthenticatedLayout>
    );
}
