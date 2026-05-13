import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Edit() {
    return (
        <AuthenticatedLayout>
            <Head title="Modifier le client" />
        </AuthenticatedLayout>
    );
}
