import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

function Field({ label, value }) {
    if (!value) return null;
    return (
        <div>
            <p className="text-xs font-medium uppercase tracking-wide text-gray-500">{label}</p>
            <p className="mt-1 text-sm text-gray-900">{value}</p>
        </div>
    );
}

function Badge({ children, className = '' }) {
    return (
        <span
            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${className}`}
        >
            {children}
        </span>
    );
}

const STATUS_STYLES = {
    draft: 'bg-gray-100 text-gray-700',
    issued: 'bg-blue-100 text-blue-700',
    paid: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
};

const STATUS_LABELS = {
    draft: 'Brouillon',
    issued: 'Émise',
    paid: 'Payée',
    cancelled: 'Annulée',
};

export default function Show({ invoice, can }) {
    const typeLabel = invoice.type === 'quote' ? 'Devis' : 'Facture';
    const { post, processing } = useForm({});

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            {typeLabel} {invoice.number}
                        </h2>
                        <Badge
                            className={STATUS_STYLES[invoice.status] ?? 'bg-gray-100 text-gray-700'}
                        >
                            {STATUS_LABELS[invoice.status] ?? invoice.status}
                        </Badge>
                    </div>
                    <div className="flex items-center gap-2">
                        <Link
                            href={route('invoices.index')}
                            className="inline-flex min-h-[44px] items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            ← Retour
                        </Link>
                        <a
                            href={route('invoices.print', invoice.id)}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex min-h-[44px] items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Imprimer
                        </a>
                        <a
                            href={route('invoices.pdf', invoice.id)}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex min-h-[44px] items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Télécharger PDF
                        </a>
                        {can.convert && (
                            <button
                                type="button"
                                disabled={processing}
                                onClick={() => post(route('invoices.convert', invoice.id))}
                                className="inline-flex min-h-[44px] items-center rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                            >
                                Convertir en facture
                            </button>
                        )}
                        {can.edit && (
                            <Link
                                href={route('invoices.edit', invoice.id)}
                                className="inline-flex min-h-[44px] items-center rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                            >
                                Modifier
                            </Link>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`${typeLabel} ${invoice.number}`} />

            <div className="py-8">
                <div className="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                    {/* Meta */}
                    <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div className="border-b px-6 py-4">
                            <h3 className="text-sm font-semibold text-gray-700">Informations</h3>
                        </div>
                        <div className="grid grid-cols-2 gap-6 px-6 py-4 sm:grid-cols-4">
                            <Field label="Numéro" value={invoice.number} />
                            <Field label="Date d'émission" value={invoice.issue_date} />
                            <Field label="Échéance" value={invoice.due_date ?? '—'} />
                            <Field label="Créé par" value={invoice.created_by} />
                        </div>
                    </div>

                    {/* Customer + Vehicle */}
                    <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div className="border-b px-6 py-4">
                            <h3 className="text-sm font-semibold text-gray-700">Client</h3>
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
                    <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div className="border-b px-6 py-4">
                            <h3 className="text-sm font-semibold text-gray-700">Lignes</h3>
                        </div>
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-start text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Description
                                    </th>
                                    <th className="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Qté
                                    </th>
                                    <th className="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-gray-500">
                                        P.U. HT
                                    </th>
                                    <th className="px-6 py-3 text-end text-xs font-medium uppercase tracking-wider text-gray-500">
                                        Total HT
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {invoice.items.map((item) => (
                                    <tr key={item.id}>
                                        <td className="px-6 py-3 text-sm text-gray-900">
                                            {item.description}
                                        </td>
                                        <td className="px-6 py-3 text-end text-sm text-gray-700">
                                            {item.quantity}
                                        </td>
                                        <td className="px-6 py-3 text-end text-sm text-gray-700">
                                            {Number(item.unit_price_ht).toFixed(2)} DH
                                        </td>
                                        <td className="px-6 py-3 text-end text-sm font-medium text-gray-900">
                                            {Number(item.line_total_ht).toFixed(2)} DH
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {/* Totals */}
                        <div className="flex justify-end border-t px-6 py-4">
                            <dl className="w-64 space-y-2 text-sm">
                                <div className="flex justify-between">
                                    <dt className="text-gray-500">Sous-total HT</dt>
                                    <dd className="text-gray-900">
                                        {Number(invoice.subtotal_ht).toFixed(2)} DH
                                    </dd>
                                </div>
                                {Number(invoice.discount) > 0 && (
                                    <div className="flex justify-between">
                                        <dt className="text-gray-500">Remise</dt>
                                        <dd className="text-gray-900">
                                            − {Number(invoice.discount).toFixed(2)} DH
                                        </dd>
                                    </div>
                                )}
                                <div className="flex justify-between">
                                    <dt className="text-gray-500">
                                        TVA ({Number(invoice.tva_rate).toFixed(0)} %)
                                    </dt>
                                    <dd className="text-gray-900">
                                        {Number(invoice.tva_amount).toFixed(2)} DH
                                    </dd>
                                </div>
                                <div className="flex justify-between border-t pt-2 text-base font-semibold">
                                    <dt>Total TTC</dt>
                                    <dd>{Number(invoice.total_ttc).toFixed(2)} DH</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {/* Notes */}
                    {invoice.notes && (
                        <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                            <div className="border-b px-6 py-4">
                                <h3 className="text-sm font-semibold text-gray-700">Notes</h3>
                            </div>
                            <p className="px-6 py-4 text-sm text-gray-700">{invoice.notes}</p>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
