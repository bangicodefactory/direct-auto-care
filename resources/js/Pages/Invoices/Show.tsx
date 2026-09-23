import Modal from '@/Components/Modal';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { useShowUnits } from '@/hooks/useShowUnits';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { pluralizeUnit } from '@/lib/utils';
import type { InvoiceStatus, InvoiceType } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/react';
import { ChevronDown, Printer } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';

interface FieldProps {
    label: ReactNode;
    value: ReactNode;
}

interface BadgeProps {
    children: ReactNode;
    className?: string;
}

interface ShowProps {
    // Bespoke projection from InvoicesController@show (not the raw Invoice model).
    invoice: {
        id: number;
        number: string;
        type: InvoiceType;
        status: InvoiceStatus;
        issue_date: string;
        due_date: string | null;
        subtotal_ht: string;
        discount: string;
        tva_rate: string;
        tva_amount: string;
        total_ttc: string;
        notes: string | null;
        customer: { id: number; name: string; phone: string | null; email: string | null } | null;
        vehicle: { id: number; plate: string; make: string; model: string } | null;
        items: {
            id: number;
            product_id: number | null;
            service_id: number | null;
            description: string;
            unit: string | null;
            quantity: string;
            unit_price_ht: string;
            line_total_ht: string;
            vehicle: { id: number; plate: string; make: string; model: string } | null;
        }[];
        created_by: string | null;
    };
    can: {
        issue?: boolean;
        markPaid?: boolean;
        convert?: boolean;
        edit?: boolean;
        cancel?: boolean;
        delete?: boolean;
    };
}

function Field({ label, value }: FieldProps) {
    if (!value) return null;
    return (
        <div>
            <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                {label}
            </p>
            <p className="mt-1 text-sm text-foreground">{value}</p>
        </div>
    );
}

function Badge({ children, className = '' }: BadgeProps) {
    return (
        <span
            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${className}`}
        >
            {children}
        </span>
    );
}

const STATUS_STYLES: Record<string, string> = {
    draft: 'bg-muted text-foreground',
    issued: 'bg-blue-100 text-blue-700',
    paid: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
};

const STATUS_LABELS: Record<string, string> = {
    draft: 'Brouillon',
    issued: 'Émise',
    paid: 'Payée',
    cancelled: 'Annulée',
};

export default function Show({ invoice, can }: ShowProps) {
    const showUnits = useShowUnits();
    const typeLabel = invoice.type === 'quote' ? 'Devis' : 'Facture';
    const { post, delete: destroy, processing } = useForm({});
    const [confirmingCancel, setConfirmingCancel] = useState(false);
    const [confirmingDelete, setConfirmingDelete] = useState(false);
    const hasLineVehicles = invoice.items.some((item) => item.vehicle);
    const hasProductLines = invoice.items.some((item) => item.product_id !== null);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <h2 className="text-xl font-semibold leading-tight text-foreground">
                            {typeLabel} {invoice.number}
                        </h2>
                        <Badge
                            className={STATUS_STYLES[invoice.status] ?? 'bg-muted text-foreground'}
                        >
                            {STATUS_LABELS[invoice.status] ?? invoice.status}
                        </Badge>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={route('invoices.index')}>← Retour</Link>
                        </Button>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline">
                                    <Printer className="h-4 w-4" />
                                    <span className="ms-2 hidden sm:inline">Documents</span>
                                    <ChevronDown className="ms-1 h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem asChild>
                                    <a
                                        href={route('invoices.print', invoice.id)}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        Imprimer
                                    </a>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <a
                                        href={route('invoices.pdf', invoice.id)}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        Télécharger PDF
                                    </a>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                        {can.issue && (
                            <Button
                                type="button"
                                disabled={processing}
                                onClick={() => post(route('invoices.issue', invoice.id))}
                            >
                                Émettre
                            </Button>
                        )}
                        {can.markPaid && (
                            <Button
                                type="button"
                                disabled={processing}
                                onClick={() => post(route('invoices.pay', invoice.id))}
                            >
                                Marquer comme payée
                            </Button>
                        )}
                        {can.convert && (
                            <Button
                                type="button"
                                disabled={processing}
                                onClick={() => post(route('invoices.convert', invoice.id))}
                            >
                                Convertir en facture
                            </Button>
                        )}
                        {can.edit && (
                            <Button asChild>
                                <Link href={route('invoices.edit', invoice.id)}>Modifier</Link>
                            </Button>
                        )}
                        {can.cancel && (
                            <Button
                                type="button"
                                variant="destructive"
                                disabled={processing}
                                onClick={() => setConfirmingCancel(true)}
                            >
                                Annuler
                            </Button>
                        )}
                        {can.delete && (
                            <Button
                                type="button"
                                variant="destructive"
                                disabled={processing}
                                onClick={() => setConfirmingDelete(true)}
                            >
                                Supprimer
                            </Button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`${typeLabel} ${invoice.number}`} />

            <div className="py-8">
                <div className="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                    {/* Meta */}
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <div className="border-b border-border px-6 py-4">
                            <h3 className="text-sm font-semibold text-foreground">Informations</h3>
                        </div>
                        <div className="grid grid-cols-2 gap-6 px-6 py-4 sm:grid-cols-4">
                            <Field label="Numéro" value={invoice.number} />
                            <Field label="Date d'émission" value={invoice.issue_date} />
                            <Field label="Échéance" value={invoice.due_date ?? '—'} />
                            <Field label="Créé par" value={invoice.created_by} />
                        </div>
                    </div>

                    {/* Customer + Vehicle */}
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <div className="border-b border-border px-6 py-4">
                            <h3 className="text-sm font-semibold text-foreground">Client</h3>
                        </div>
                        <div className="grid grid-cols-2 gap-6 px-6 py-4 sm:grid-cols-3">
                            <Field label="Nom" value={invoice.customer?.name} />
                            <Field label="Téléphone" value={invoice.customer?.phone} />
                            <Field label="E-mail" value={invoice.customer?.email} />
                            {invoice.vehicle && (
                                <>
                                    <Field label="Plaque" value={invoice.vehicle.plate} />
                                    <Field
                                        label="Véhicule"
                                        value={`${invoice.vehicle.make} ${invoice.vehicle.model}`}
                                    />
                                </>
                            )}
                        </div>
                    </div>

                    {/* Line items */}
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <div className="border-b border-border px-6 py-4">
                            <h3 className="text-sm font-semibold text-foreground">Lignes</h3>
                        </div>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Description</TableHead>
                                    {hasLineVehicles && <TableHead>Véhicule</TableHead>}
                                    <TableHead className="text-end">Qté</TableHead>
                                    <TableHead className="text-end">P.U. HT</TableHead>
                                    <TableHead className="text-end">Total HT</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody className="tabular-nums">
                                {invoice.items.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="text-foreground">
                                            {item.description}
                                        </TableCell>
                                        {hasLineVehicles && (
                                            <TableCell className="text-muted-foreground">
                                                {item.vehicle ? (
                                                    <>
                                                        {item.vehicle.plate}
                                                        <span className="ms-1 text-xs text-muted-foreground/70">
                                                            {item.vehicle.make} {item.vehicle.model}
                                                        </span>
                                                    </>
                                                ) : (
                                                    '—'
                                                )}
                                            </TableCell>
                                        )}
                                        <TableCell className="text-end text-muted-foreground">
                                            {item.quantity}
                                            {showUnits &&
                                                item.unit &&
                                                ` ${pluralizeUnit(Number(item.quantity), item.unit)}`}
                                        </TableCell>
                                        <TableCell className="text-end text-muted-foreground">
                                            {Number(item.unit_price_ht).toFixed(2)} DH
                                        </TableCell>
                                        <TableCell className="text-end font-medium text-foreground">
                                            {Number(item.line_total_ht).toFixed(2)} DH
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {/* Totals */}
                        <div className="flex justify-end border-t border-border px-6 py-4">
                            <dl className="w-64 space-y-2 text-sm tabular-nums">
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Sous-total HT</dt>
                                    <dd className="text-foreground">
                                        {Number(invoice.subtotal_ht).toFixed(2)} DH
                                    </dd>
                                </div>
                                {Number(invoice.discount) > 0 && (
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">Remise</dt>
                                        <dd className="text-foreground">
                                            − {Number(invoice.discount).toFixed(2)} DH
                                        </dd>
                                    </div>
                                )}
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">
                                        TVA ({Number(invoice.tva_rate).toFixed(0)} %)
                                    </dt>
                                    <dd className="text-foreground">
                                        {Number(invoice.tva_amount).toFixed(2)} DH
                                    </dd>
                                </div>
                                <div className="flex justify-between border-t border-border pt-2 text-base font-semibold">
                                    <dt>Total TTC</dt>
                                    <dd>{Number(invoice.total_ttc).toFixed(2)} DH</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {/* Notes */}
                    {invoice.notes && (
                        <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                            <div className="border-b border-border px-6 py-4">
                                <h3 className="text-sm font-semibold text-foreground">Notes</h3>
                            </div>
                            <p className="px-6 py-4 text-sm text-foreground">{invoice.notes}</p>
                        </div>
                    )}
                </div>
            </div>

            <Modal show={confirmingCancel} maxWidth="sm" onClose={() => setConfirmingCancel(false)}>
                <div className="p-6">
                    <h2 className="text-lg font-medium text-foreground">Annuler ce document ?</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {typeLabel} <span className="font-medium">{invoice.number}</span> sera
                        marqué(e) comme annulé(e). Cette action est irréversible.
                        {invoice.type === 'invoice' && hasProductLines && (
                            <> Le stock des pièces facturées sera restitué à l&apos;inventaire.</>
                        )}
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <Button variant="secondary" onClick={() => setConfirmingCancel(false)}>
                            Fermer
                        </Button>
                        <Button
                            variant="destructive"
                            disabled={processing}
                            onClick={() => {
                                setConfirmingCancel(false);
                                post(route('invoices.cancel', invoice.id));
                            }}
                        >
                            Confirmer l&apos;annulation
                        </Button>
                    </div>
                </div>
            </Modal>

            <Modal show={confirmingDelete} maxWidth="sm" onClose={() => setConfirmingDelete(false)}>
                <div className="p-6">
                    <h2 className="text-lg font-medium text-foreground">Supprimer ce document ?</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {typeLabel} <span className="font-medium">{invoice.number}</span> sera
                        supprimé(e) de l&apos;application. S&apos;il porte le dernier numéro de la
                        série, ce numéro sera réutilisé pour le prochain document.
                        {invoice.type === 'invoice' &&
                            hasProductLines &&
                            invoice.status !== 'cancelled' && (
                                <>
                                    {' '}
                                    Le stock des pièces facturées sera restitué à l&apos;inventaire.
                                </>
                            )}
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <Button variant="secondary" onClick={() => setConfirmingDelete(false)}>
                            Fermer
                        </Button>
                        <Button
                            variant="destructive"
                            disabled={processing}
                            onClick={() => {
                                setConfirmingDelete(false);
                                destroy(route('invoices.destroy', invoice.id));
                            }}
                        >
                            Confirmer la suppression
                        </Button>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
