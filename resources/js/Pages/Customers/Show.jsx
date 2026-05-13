import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Show() {
    return (
        <AuthenticatedLayout>
            <Head title="Client" />
        </AuthenticatedLayout>
    );
}
