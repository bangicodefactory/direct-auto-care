import Modal from '@/Components/Modal';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { ReminderStatus, ReminderType } from '@/types/models';
import type { Paginated } from '@/types/pagination';
import { Head, Link, router } from '@inertiajs/react';
import { MoreVertical } from 'lucide-react';
import { useState } from 'react';

const STATUS_COLORS: Record<ReminderStatus, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    sent: 'bg-green-100 text-green-800',
    acknowledged: 'bg-blue-100 text-blue-800',
    cancelled: 'bg-muted text-foreground',
};

interface ReminderRow {
    id: number;
    type: { value: ReminderType; label: string };
    status: { value: ReminderStatus; label: string };
    scheduled_for: string;
    sent_at: string | null;
    error_message: string | null;
    customer: {
        id: number;
        name: string;
    };
    vehicle: {
        id: number;
        license_plate: string;
    };
    can: {
        trigger: boolean;
        cancel: boolean;
    };
}

interface Filters {
    status?: string;
    type?: string;
    date_from?: string;
    date_to?: string;
}

interface FilterOption {
    value: string;
    label: string;
}

interface IndexProps {
    reminders: Paginated<ReminderRow>;
    filters: Filters;
    statuses: FilterOption[];
    types: FilterOption[];
}

export default function Index({ reminders, filters, statuses, types }: IndexProps) {
    const [triggeringReminder, setTriggeringReminder] = useState<ReminderRow | null>(null);
    const [cancellingReminder, setCancellingReminder] = useState<ReminderRow | null>(null);
    const [processing, setProcessing] = useState(false);

    function applyFilters(changes: Partial<Filters> & { page?: undefined }) {
        router.get(
            route('reminders.index'),
            { ...filters, ...changes },
            { preserveState: true, replace: true },
        );
    }

    function handleTrigger() {
        setProcessing(true);
        router.post(
            route('reminders.trigger', triggeringReminder!.id),
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
            route('reminders.cancel', cancellingReminder!.id),
            {},
            {
                onFinish: () => {
                    setProcessing(false);
                    setCancellingReminder(null);
                },
            },
        );
    }

    const hasFilters = filters?.status || filters?.type || filters?.date_from || filters?.date_to;

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-foreground">Rappels</h2>
            }
        >
            <Head title="Rappels" />

            <div className="py-8">
                <div className="px-4 sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        {/* FILTERS */}
                        <div className="flex flex-wrap items-end gap-3 border-b border-border p-4">
                            <div className="flex flex-col gap-1">
                                <label className="text-xs font-medium text-muted-foreground">
                                    Statut
                                </label>
                                <Select
                                    value={filters?.status || '__all__'}
                                    onValueChange={(v) =>
                                        applyFilters({
                                            status: v === '__all__' ? undefined : v,
                                            page: undefined,
                                        })
                                    }
                                >
                                    <SelectTrigger className="min-h-[44px] w-44">
                                        <SelectValue placeholder="Tous les statuts" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__all__">Tous les statuts</SelectItem>
                                        {statuses.map((s) => (
                                            <SelectItem key={s.value} value={s.value}>
                                                {s.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex flex-col gap-1">
                                <label className="text-xs font-medium text-muted-foreground">
                                    Type
                                </label>
                                <Select
                                    value={filters?.type || '__all__'}
                                    onValueChange={(v) =>
                                        applyFilters({
                                            type: v === '__all__' ? undefined : v,
                                            page: undefined,
                                        })
                                    }
                                >
                                    <SelectTrigger className="min-h-[44px] w-44">
                                        <SelectValue placeholder="Tous les types" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__all__">Tous les types</SelectItem>
                                        {types.map((t) => (
                                            <SelectItem key={t.value} value={t.value}>
                                                {t.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex flex-col gap-1">
                                <label
                                    htmlFor="date_from"
                                    className="text-xs font-medium text-muted-foreground"
                                >
                                    Du
                                </label>
                                <input
                                    id="date_from"
                                    type="date"
                                    className="min-h-[44px] rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
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
                                <label
                                    htmlFor="date_to"
                                    className="text-xs font-medium text-muted-foreground"
                                >
                                    Au
                                </label>
                                <input
                                    id="date_to"
                                    type="date"
                                    className="min-h-[44px] rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    value={filters?.date_to ?? ''}
                                    onChange={(e) =>
                                        applyFilters({
                                            date_to: e.target.value || undefined,
                                            page: undefined,
                                        })
                                    }
                                />
                            </div>

                            {hasFilters && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() =>
                                        applyFilters({
                                            status: undefined,
                                            type: undefined,
                                            date_from: undefined,
                                            date_to: undefined,
                                            page: undefined,
                                        })
                                    }
                                >
                                    Effacer
                                </Button>
                            )}
                        </div>

                        {/* TABLE */}
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Client</TableHead>
                                    <TableHead>Véhicule</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Statut</TableHead>
                                    <TableHead>Planifié le</TableHead>
                                    <TableHead>Envoyé le</TableHead>
                                    <TableHead />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {reminders.data.map((r) => (
                                    <TableRow key={r.id}>
                                        <TableCell className="whitespace-nowrap font-medium text-foreground">
                                            <Link
                                                href={route('customers.show', r.customer.id)}
                                                className="hover:underline"
                                            >
                                                {r.customer.name}
                                            </Link>
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-muted-foreground">
                                            <Link
                                                href={route('vehicles.show', r.vehicle.id)}
                                                className="hover:underline"
                                            >
                                                {r.vehicle.license_plate}
                                            </Link>
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-muted-foreground">
                                            {r.type.label}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap">
                                            <span
                                                className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${STATUS_COLORS[r.status.value] ?? 'bg-muted text-foreground'}`}
                                            >
                                                {r.status.label}
                                            </span>
                                            {r.error_message && (
                                                <p
                                                    className="mt-1 text-xs text-destructive"
                                                    title={r.error_message}
                                                >
                                                    Erreur
                                                </p>
                                            )}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-muted-foreground">
                                            {r.scheduled_for}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-muted-foreground">
                                            {r.sent_at ?? '—'}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-end">
                                            {(r.can.trigger || r.can.cancel) && (
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <button className="flex min-h-[44px] min-w-[44px] items-center justify-center rounded text-muted-foreground hover:bg-accent hover:text-accent-foreground focus:outline-none">
                                                            <MoreVertical className="h-5 w-5" />
                                                        </button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent
                                                        align="end"
                                                        className="w-48"
                                                    >
                                                        {r.can.trigger && (
                                                            <DropdownMenuItem
                                                                onClick={() =>
                                                                    setTriggeringReminder(r)
                                                                }
                                                            >
                                                                Envoyer maintenant
                                                            </DropdownMenuItem>
                                                        )}
                                                        {r.can.cancel && (
                                                            <DropdownMenuItem
                                                                className="text-destructive focus:text-destructive"
                                                                onClick={() =>
                                                                    setCancellingReminder(r)
                                                                }
                                                            >
                                                                Annuler
                                                            </DropdownMenuItem>
                                                        )}
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {reminders.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            Aucun rappel trouvé.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>

                        {/* PAGINATION */}
                        {reminders.links.length > 3 && (
                            <div className="flex items-center justify-between border-t border-border px-6 py-3">
                                {reminders.links.map((link, i) =>
                                    link.url ? (
                                        <Link
                                            key={i}
                                            href={link.url}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className={`inline-flex min-h-[44px] items-center px-3 text-sm ${link.active ? 'font-bold text-primary' : 'text-muted-foreground hover:text-foreground'}`}
                                        />
                                    ) : (
                                        <span
                                            key={i}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            className="inline-flex min-h-[44px] items-center px-3 text-sm text-muted-foreground/40"
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
                    <h2 className="text-lg font-medium text-foreground">
                        Envoyer ce rappel maintenant ?
                    </h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Le rappel sera envoyé immédiatement à{' '}
                        <span className="font-medium">{triggeringReminder?.customer?.name}</span>{' '}
                        pour le véhicule{' '}
                        <span className="font-medium">
                            {triggeringReminder?.vehicle?.license_plate}
                        </span>
                        .
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <Button variant="secondary" onClick={() => setTriggeringReminder(null)}>
                            Annuler
                        </Button>
                        <Button onClick={handleTrigger} disabled={processing}>
                            Envoyer
                        </Button>
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
                    <h2 className="text-lg font-medium text-foreground">Annuler ce rappel ?</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Le rappel de{' '}
                        <span className="font-medium">{cancellingReminder?.customer?.name}</span> (
                        {cancellingReminder?.vehicle?.license_plate}) sera annulé. Il ne sera plus
                        envoyé automatiquement.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <Button variant="secondary" onClick={() => setCancellingReminder(null)}>
                            Fermer
                        </Button>
                        <Button variant="destructive" onClick={handleCancel} disabled={processing}>
                            Annuler le rappel
                        </Button>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
