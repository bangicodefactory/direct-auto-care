import DangerButton from '@/Components/DangerButton';
import Dropdown from '@/Components/Dropdown';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { MoreVertical } from 'lucide-react';
import { useState } from 'react';

const STATUS_COLORS = {
    pending: 'bg-yellow-100 text-yellow-800',
    sent: 'bg-green-100 text-green-800',
    acknowledged: 'bg-blue-100 text-blue-800',
    cancelled: 'bg-gray-100 text-gray-700',
};

export default function Index({ reminders, filters, statuses, types }) {
    const [triggeringReminder, setTriggeringReminder] = useState(null);
    const [cancellingReminder, setCancellingReminder] = useState(null);
    const [processing, setProcessing] = useState(false);

    function applyFilters(changes) {
        router.get(
            route('reminders.index'),
            { ...filters, ...changes },
            {
                preserveState: true,
                replace: true,
            },
        );
    }

    function handleTrigger() {
        setProcessing(true);
        router.post(
            route('reminders.trigger', triggeringReminder.id),
            {},
            {
                onFinish: () => {
                    setProcessing(false);
                    setTriggeringReminder(null);
                },
            },
        );
    }

    function handleCancel() {
        setProcessing(true);
        router.post(
            route('reminders.cancel', cancellingReminder.id),
            {},
            {
                onFinish: () => {
                    setProcessing(false);
                    setCancellingReminder(null);
                },
            },
        );
    }

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Rappels</h2>}
        >
            <Head title="Rappels" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        {/* FILTERS */}
                        <div className="flex flex-wrap items-end gap-3 border-b p-4">
                            <div className="flex flex-col gap-1">
                                <label className="text-xs font-medium text-gray-600">Statut</label>
                                <select
                                    className="rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    value={filters?.status ?? ''}
                                    onChange={(e) =>
                                        applyFilters({
                                            status: e.target.value || undefined,
                                            page: undefined,
                                        })
                                    }
                                >
                                    <option value="">Tous les statuts</option>
                                    {statuses.map((s) => (
                                        <option key={s.value} value={s.value}>
                                            {s.label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="flex flex-col gap-1">
                                <label className="text-xs font-medium text-gray-600">Type</label>
                                <select
                                    className="rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    value={filters?.type ?? ''}
                                    onChange={(e) =>
                                        applyFilters({
                                            type: e.target.value || undefined,
                                            page: undefined,
                                        })
                                    }
                                >
                                    <option value="">Tous les types</option>
                                    {types.map((t) => (
                                        <option key={t.value} value={t.value}>
                                            {t.label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="flex flex-col gap-1">
                                <label className="text-xs font-medium text-gray-600">Du</label>
                                <input
                                    type="date"
                                    className="rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    value={filters?.date_from ?? ''}
                                    onChange={(e) =>
                                        applyFilters({
                                            date_from: e.target.value || undefined,
                                            page: undefined,
                                        })
                                    }
                                />
                            </div>

                            <div className="flex flex-col gap-1">
                                <label className="text-xs font-medium text-gray-600">Au</label>
                                <input
                                    type="date"
                                    className="rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    value={filters?.date_to ?? ''}
                                    onChange={(e) =>
                                        applyFilters({
                                            date_to: e.target.value || undefined,
                                            page: undefined,
                                        })
                                    }
                                />
                            </div>

                            {(filters?.status ||
                                filters?.type ||
                                filters?.date_from ||
                                filters?.date_to) && (
                                <button
                                    onClick={() =>
                                        applyFilters({
                                            status: undefined,
                                            type: undefined,
                                            date_from: undefined,
                                            date_to: undefined,
                                            page: undefined,
                                        })
                                    }
                                    className="rounded-md border px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                                >
                                    Effacer
                                </button>
                            )}
                        </div>

                        {/* TABLE */}
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Client
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Véhicule
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Type
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Statut
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Planifié le
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Envoyé le
                                        </th>
                                        <th className="px-6 py-3" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white">
                                    {reminders.data.map((r) => (
                                        <tr key={r.id}>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                                <Link
                                                    href={route('customers.show', r.customer.id)}
                                                    className="hover:underline"
                                                >
                                                    {r.customer.name}
                                                </Link>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                                <Link
                                                    href={route('vehicles.show', r.vehicle.id)}
                                                    className="hover:underline"
                                                >
                                                    {r.vehicle.license_plate}
                                                </Link>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                                {r.type.label}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm">
                                                <span
                                                    className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${STATUS_COLORS[r.status.value] ?? 'bg-gray-100 text-gray-700'}`}
                                                >
                                                    {r.status.label}
                                                </span>
                                                {r.error_message && (
                                                    <p
                                                        className="mt-1 text-xs text-red-600"
                                                        title={r.error_message}
                                                    >
                                                        Erreur
                                                    </p>
                                                )}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                                {r.scheduled_for}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                                {r.sent_at ?? '—'}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                                {(r.can.trigger || r.can.cancel) && (
                                                    <Dropdown>
                                                        <Dropdown.Trigger>
                                                            <button className="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-none">
                                                                <MoreVertical className="h-5 w-5" />
                                                            </button>
                                                        </Dropdown.Trigger>
                                                        <Dropdown.Content align="right" width="48">
                                                            {r.can.trigger && (
                                                                <button
                                                                    className="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:outline-none"
                                                                    onClick={() =>
                                                                        setTriggeringReminder(r)
                                                                    }
                                                                >
                                                                    Envoyer maintenant
                                                                </button>
                                                            )}
                                                            {r.can.cancel && (
                                                                <button
                                                                    className="block w-full px-4 py-2 text-start text-sm leading-5 text-red-600 transition duration-150 ease-in-out hover:bg-red-50 focus:outline-none"
                                                                    onClick={() =>
                                                                        setCancellingReminder(r)
                                                                    }
                                                                >
                                                                    Annuler
                                                                </button>
                                                            )}
                                                        </Dropdown.Content>
                                                    </Dropdown>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                    {reminders.data.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={7}
                                                className="px-6 py-8 text-center text-sm text-gray-500"
                                            >
                                                Aucun rappel trouvé.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* PAGINATION */}
                        {reminders.links.length > 3 && (
                            <div className="flex items-center justify-between border-t border-gray-200 px-6 py-3">
                                {reminders.links.map((link, i) =>
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

            {/* TRIGGER MODAL */}
            <Modal
                show={triggeringReminder !== null}
                maxWidth="sm"
                onClose={() => setTriggeringReminder(null)}
            >
                <div className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">
                        Envoyer ce rappel maintenant ?
                    </h2>
                    <p className="mt-1 text-sm text-gray-600">
                        Le rappel sera envoyé immédiatement à{' '}
                        <span className="font-medium">{triggeringReminder?.customer?.name}</span>{' '}
                        pour le véhicule{' '}
                        <span className="font-medium">
                            {triggeringReminder?.vehicle?.license_plate}
                        </span>
                        .
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setTriggeringReminder(null)}>
                            Annuler
                        </SecondaryButton>
                        <PrimaryButton onClick={handleTrigger} disabled={processing}>
                            Envoyer
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>

            {/* CANCEL MODAL */}
            <Modal
                show={cancellingReminder !== null}
                maxWidth="sm"
                onClose={() => setCancellingReminder(null)}
            >
                <div className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">Annuler ce rappel ?</h2>
                    <p className="mt-1 text-sm text-gray-600">
                        Le rappel de{' '}
                        <span className="font-medium">{cancellingReminder?.customer?.name}</span> (
                        {cancellingReminder?.vehicle?.license_plate}) sera annulé. Il ne sera plus
                        envoyé automatiquement.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setCancellingReminder(null)}>
                            Fermer
                        </SecondaryButton>
                        <DangerButton onClick={handleCancel} disabled={processing}>
                            Annuler le rappel
                        </DangerButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
