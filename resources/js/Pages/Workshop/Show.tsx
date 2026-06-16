import Modal from '@/Components/Modal';
import VehicleConditionDiagram from '@/Components/VehicleConditionDiagram';
import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Product, ServiceOrderItem, ServiceOrderStatus } from '@/types/models';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Check, Trash2 } from 'lucide-react';
import { useState } from 'react';

type BadgeColor = 'gray' | 'blue' | 'yellow' | 'orange' | 'purple' | 'cyan' | 'green' | 'red';

const BADGE: Record<BadgeColor, string> = {
    gray: 'bg-muted text-foreground',
    blue: 'bg-blue-100 text-blue-700',
    yellow: 'bg-yellow-100 text-yellow-800',
    orange: 'bg-orange-100 text-orange-700',
    purple: 'bg-purple-100 text-purple-700',
    cyan: 'bg-cyan-100 text-cyan-700',
    green: 'bg-green-100 text-green-700',
    red: 'bg-red-100 text-red-700',
};

const FIELD =
    'mt-1 block w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';

interface FieldProps {
    label: string;
    value?: string | null;
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

interface CardProps {
    title: string;
    action?: React.ReactNode;
    children: React.ReactNode;
}

function Card({ title, action, children }: CardProps) {
    return (
        <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
            <div className="flex items-center justify-between border-b border-border px-6 py-4">
                <h3 className="text-sm font-semibold text-foreground">{title}</h3>
                {action}
            </div>
            <div className="p-6">{children}</div>
        </div>
    );
}

interface Phase {
    value: ServiceOrderStatus;
    label: string;
    at?: string | null;
}

interface PhaseStepperProps {
    phases: Phase[];
    currentStatus: ServiceOrderStatus;
}

function PhaseStepper({ phases, currentStatus }: PhaseStepperProps) {
    const currentIndex = phases.findIndex((p) => p.value === currentStatus);

    return (
        <ol className="flex flex-wrap gap-y-4">
            {phases.map((p, i) => {
                const done = currentIndex > -1 && i < currentIndex;
                const current = p.value === currentStatus;
                return (
                    <li
                        key={p.value}
                        className="flex min-w-[88px] flex-1 flex-col items-center text-center"
                    >
                        <div className="flex w-full items-center">
                            <span className="h-0.5 flex-1 bg-transparent" />
                            <span
                                className={`flex h-7 w-7 items-center justify-center rounded-full border text-xs font-semibold ${
                                    current
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : done
                                          ? 'border-green-500 bg-green-500 text-white'
                                          : 'border-border bg-card text-muted-foreground'
                                }`}
                            >
                                {done ? <Check className="h-4 w-4" /> : i + 1}
                            </span>
                            <span
                                className={`h-0.5 flex-1 ${i < phases.length - 1 && (done || current) ? 'bg-green-500' : 'bg-border'} ${i === phases.length - 1 ? 'opacity-0' : ''}`}
                            />
                        </div>
                        <span
                            className={`mt-1.5 text-xs ${current ? 'font-semibold text-foreground' : 'text-muted-foreground'}`}
                        >
                            {p.label}
                        </span>
                        {p.at && <span className="text-[10px] text-muted-foreground">{p.at}</span>}
                    </li>
                );
            })}
        </ol>
    );
}

interface PartsManagerProps {
    order: { id: number; items: ServiceOrderItem[] };
    products: Product[];
    editable: boolean;
}

function PartsManager({ order, products, editable }: PartsManagerProps) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        product_id: string;
        description: string;
        quantity: number | string;
        unit_price_ht: string;
    }>({
        product_id: '',
        description: '',
        quantity: 1,
        unit_price_ht: '',
    });

    const total = order.items.reduce((sum, item) => sum + Number(item.line_total_ht), 0);

    function pickProduct(id: string) {
        const product = products.find((p) => String(p.id) === String(id));
        setData((current) => ({
            ...current,
            product_id: id,
            // Pre-fill price + description from the catalogue; leave the user free to override.
            unit_price_ht: product ? product.price_sell : current.unit_price_ht,
            description: product ? '' : current.description,
        }));
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(route('workshop.items.store', order.id), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    }

    function remove(itemId: number) {
        router.delete(route('workshop.items.destroy', [order.id, itemId]), {
            preserveScroll: true,
        });
    }

    if (!editable && order.items.length === 0) {
        return null;
    }

    return (
        <Card title="Pièces & main d'œuvre">
            {order.items.length > 0 ? (
                <ul className="divide-y divide-border text-sm">
                    {order.items.map((item) => (
                        <li key={item.id} className="flex items-center justify-between gap-3 py-2">
                            <span className="text-foreground">
                                {item.quantity} × {item.description}
                            </span>
                            <span className="flex items-center gap-3">
                                <span className="text-muted-foreground">
                                    {Number(item.line_total_ht).toFixed(2)} DH
                                </span>
                                {editable && (
                                    <button
                                        type="button"
                                        onClick={() => remove(item.id)}
                                        className="text-muted-foreground transition hover:text-destructive"
                                        aria-label="Retirer la pièce"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </button>
                                )}
                            </span>
                        </li>
                    ))}
                    <li className="flex justify-between py-2 font-semibold text-foreground">
                        <span>Total HT</span>
                        <span>{total.toFixed(2)} DH</span>
                    </li>
                </ul>
            ) : (
                <p className="text-sm text-muted-foreground">Aucune pièce ajoutée.</p>
            )}

            {editable && (
                <form onSubmit={submit} className="mt-4 space-y-3 border-t border-border pt-4">
                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="sm:col-span-2">
                            <label className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                Produit (stock)
                            </label>
                            <select
                                value={data.product_id}
                                onChange={(e) => pickProduct(e.target.value)}
                                className={FIELD}
                            >
                                <option value="">— Ligne libre (main d'œuvre)…</option>
                                {products.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.sku} — {p.name} (stock : {p.quantity_on_hand})
                                    </option>
                                ))}
                            </select>
                            {errors.product_id && (
                                <p className="mt-1 text-sm text-destructive">{errors.product_id}</p>
                            )}
                        </div>

                        {!data.product_id && (
                            <div className="sm:col-span-2">
                                <label className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    Description
                                </label>
                                <input
                                    type="text"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className={FIELD}
                                    placeholder="Ex. Main d'œuvre diagnostic"
                                />
                                {errors.description && (
                                    <p className="mt-1 text-sm text-destructive">
                                        {errors.description}
                                    </p>
                                )}
                            </div>
                        )}

                        <div>
                            <label className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                Quantité
                            </label>
                            <input
                                type="number"
                                min="1"
                                step="1"
                                value={data.quantity}
                                onChange={(e) => setData('quantity', e.target.value)}
                                className={FIELD}
                            />
                            {errors.quantity && (
                                <p className="mt-1 text-sm text-destructive">{errors.quantity}</p>
                            )}
                        </div>

                        <div>
                            <label className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                Prix unitaire HT (DH)
                            </label>
                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.unit_price_ht}
                                onChange={(e) => setData('unit_price_ht', e.target.value)}
                                className={FIELD}
                            />
                            {errors.unit_price_ht && (
                                <p className="mt-1 text-sm text-destructive">
                                    {errors.unit_price_ht}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            Ajouter la pièce
                        </Button>
                    </div>
                </form>
            )}
        </Card>
    );
}

// Bespoke projection from ServiceOrderController@show (summary() + extra fields);
// NOT the raw ServiceOrder model — phase timestamps live in `phases`, not here.
interface WorkshopShowOrder {
    id: number;
    number: string;
    status: ServiceOrderStatus;
    status_label: string;
    status_color: BadgeColor;
    customer: string | null;
    vehicle: string | null;
    updated_at: string | null;
    mileage_in: number | null;
    fuel_level: number | null;
    reception_condition: Record<string, string> | null;
    reception_photos: string[];
    signature_url: string | null;
    diagnosis_notes: string | null;
    internal_notes: string | null;
    received_at: string | null;
    cancelled_at: string | null;
    items: ServiceOrderItem[];
    quote: { id: number; number: string } | null;
    invoice: { id: number; number: string } | null;
}

interface ConfirmState {
    kind: 'advance' | 'cancel';
}

interface ShowProps {
    order: WorkshopShowOrder;
    phases: Phase[];
    next: { value: ServiceOrderStatus; label: string } | null;
    can: { update: boolean };
    products?: Product[];
}

export default function Show({ order, phases, next, can, products = [] }: ShowProps) {
    const isCancelled = order.status === 'cancelled';
    const isClosed = order.status === 'closed';
    const diagnosisEditable = order.status === 'diagnosis' || order.status === 'in_progress';

    const { data, setData, put, processing, errors } = useForm<{
        mileage_in: number | string;
        diagnosis_notes: string;
        internal_notes: string;
    }>({
        mileage_in: order.mileage_in ?? '',
        diagnosis_notes: order.diagnosis_notes ?? '',
        internal_notes: order.internal_notes ?? '',
    });

    const [confirm, setConfirm] = useState<ConfirmState | null>(null); // { kind: 'advance'|'cancel' }
    const [busy, setBusy] = useState(false); // a phase transition (advance/cancel) is in flight
    const hasCondition =
        order.reception_condition && Object.keys(order.reception_condition).length > 0;

    function saveNotes(e: React.FormEvent) {
        e.preventDefault();
        put(route('workshop.update', order.id), { preserveScroll: true });
    }

    function doAdvance() {
        router.patch(
            route('workshop.advance', order.id),
            {},
            {
                preserveScroll: true,
                onStart: () => setBusy(true),
                onFinish: () => setBusy(false),
            },
        );
        setConfirm(null);
    }

    function doCancel() {
        router.patch(
            route('workshop.cancel', order.id),
            {},
            {
                preserveScroll: true,
                onStart: () => setBusy(true),
                onFinish: () => setBusy(false),
            },
        );
        setConfirm(null);
    }

    function handleAdvanceClick() {
        // Confirm the irreversible final step; advance the rest directly.
        if (next?.value === 'closed') {
            setConfirm({ kind: 'advance' });
        } else {
            doAdvance();
        }
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <h2 className="text-xl font-semibold leading-tight text-foreground">
                            Ordre {order.number}
                        </h2>
                        <span
                            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${BADGE[order.status_color] ?? BADGE.gray}`}
                        >
                            {order.status_label}
                        </span>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={route('workshop.index')}>← Atelier</Link>
                        </Button>
                        {can.update && next && !isCancelled && (
                            <Button onClick={handleAdvanceClick} disabled={busy}>
                                Passer à : {next.label}
                            </Button>
                        )}
                        {can.update && !isCancelled && !isClosed && (
                            <Button
                                variant="destructive"
                                onClick={() => setConfirm({ kind: 'cancel' })}
                                disabled={busy}
                            >
                                Annuler
                            </Button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Ordre ${order.number}`} />

            <div className="py-8">
                <div className="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                    {isCancelled && (
                        <div className="rounded-lg border border-destructive/30 bg-destructive/10 px-6 py-4 text-sm text-destructive">
                            Ordre annulé{order.cancelled_at ? ` le ${order.cancelled_at}` : ''}.
                        </div>
                    )}

                    {/* Phase stepper */}
                    {!isCancelled && (
                        <Card title="Suivi des phases">
                            <PhaseStepper phases={phases} currentStatus={order.status} />
                        </Card>
                    )}

                    {/* Reception info */}
                    <Card title="Réception">
                        <div className="grid grid-cols-2 gap-6 sm:grid-cols-4">
                            <Field label="Client" value={order.customer} />
                            <Field label="Véhicule" value={order.vehicle} />
                            <Field
                                label="Kilométrage"
                                value={order.mileage_in ? `${order.mileage_in} km` : null}
                            />
                            <Field
                                label="Carburant"
                                value={order.fuel_level != null ? `${order.fuel_level} %` : null}
                            />
                            <Field label="Reçu le" value={order.received_at} />
                        </div>
                    </Card>

                    {/* Quote / Invoice links */}
                    {(order.quote || order.invoice) && (
                        <Card title="Documents">
                            <div className="flex flex-wrap gap-3 text-sm">
                                {order.quote && (
                                    <Link
                                        href={route('invoices.show', order.quote.id)}
                                        className="font-medium text-secondary hover:underline"
                                    >
                                        Devis {order.quote.number} →
                                    </Link>
                                )}
                                {order.invoice && (
                                    <Link
                                        href={route('invoices.show', order.invoice.id)}
                                        className="font-medium text-secondary hover:underline"
                                    >
                                        Facture {order.invoice.number} →
                                    </Link>
                                )}
                            </div>
                        </Card>
                    )}

                    {/* Notes (editable) */}
                    {can.update && !isCancelled ? (
                        <form onSubmit={saveNotes}>
                            <Card
                                title="Notes"
                                action={
                                    <Button type="submit" size="sm" disabled={processing}>
                                        Enregistrer
                                    </Button>
                                }
                            >
                                <div className="space-y-4">
                                    <div>
                                        <label
                                            htmlFor="diagnosis_notes"
                                            className="text-xs font-medium uppercase tracking-wide text-muted-foreground"
                                        >
                                            Notes de diagnostic
                                            {!diagnosisEditable && ' (lecture seule à cette phase)'}
                                        </label>
                                        {diagnosisEditable ? (
                                            <textarea
                                                id="diagnosis_notes"
                                                rows={3}
                                                className={FIELD}
                                                value={data.diagnosis_notes}
                                                onChange={(e) =>
                                                    setData('diagnosis_notes', e.target.value)
                                                }
                                                placeholder="Constat, pièces à prévoir…"
                                            />
                                        ) : (
                                            <p className="mt-1 text-sm text-foreground">
                                                {order.diagnosis_notes || '—'}
                                            </p>
                                        )}
                                    </div>
                                    <div>
                                        <label
                                            htmlFor="internal_notes"
                                            className="text-xs font-medium uppercase tracking-wide text-muted-foreground"
                                        >
                                            Notes internes
                                        </label>
                                        <textarea
                                            id="internal_notes"
                                            rows={3}
                                            className={FIELD}
                                            value={data.internal_notes}
                                            onChange={(e) =>
                                                setData('internal_notes', e.target.value)
                                            }
                                        />
                                        {errors.internal_notes && (
                                            <p className="mt-1 text-xs text-destructive">
                                                {errors.internal_notes}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </Card>
                        </form>
                    ) : (
                        (order.diagnosis_notes || order.internal_notes) && (
                            <Card title="Notes">
                                <div className="space-y-3">
                                    <Field label="Diagnostic" value={order.diagnosis_notes} />
                                    <Field label="Notes internes" value={order.internal_notes} />
                                </div>
                            </Card>
                        )
                    )}

                    {/* Parts & labour — editable while the order is in progress */}
                    <PartsManager
                        order={order}
                        products={products}
                        editable={can.update && order.status === 'in_progress'}
                    />

                    {/* Condition diagram */}
                    <Card title="État du véhicule à la réception">
                        {hasCondition ? (
                            <VehicleConditionDiagram value={order.reception_condition} readOnly />
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Aucun dommage marqué à la réception.
                            </p>
                        )}
                    </Card>

                    {/* Photos */}
                    <Card title="Photos du véhicule">
                        {order.reception_photos?.length ? (
                            <div className="flex flex-wrap gap-3">
                                {order.reception_photos.map((url) => (
                                    <a key={url} href={url} target="_blank" rel="noreferrer">
                                        <img
                                            src={url}
                                            alt="Photo du véhicule"
                                            className="h-32 w-44 rounded-md border border-border object-cover transition hover:opacity-90"
                                        />
                                    </a>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">Aucune photo.</p>
                        )}
                    </Card>

                    {/* Signature */}
                    <Card title="Signature du client">
                        {order.signature_url ? (
                            <img
                                src={order.signature_url}
                                alt="Signature du client"
                                className="max-h-44 rounded-md border border-border bg-white"
                            />
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Aucune signature enregistrée.
                            </p>
                        )}
                    </Card>
                </div>
            </div>

            <Modal show={confirm !== null} maxWidth="sm" onClose={() => setConfirm(null)}>
                <div className="p-6">
                    <h2 className="text-lg font-medium text-foreground">
                        {confirm?.kind === 'cancel'
                            ? 'Annuler cet ordre de service ?'
                            : 'Clôturer cet ordre de service ?'}
                    </h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {confirm?.kind === 'cancel'
                            ? "L'ordre sera marqué comme annulé. Cette action est définitive."
                            : "L'ordre passera au statut « Clôturé ». Cette action est définitive."}
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <Button variant="secondary" onClick={() => setConfirm(null)}>
                            Retour
                        </Button>
                        <Button
                            variant={confirm?.kind === 'cancel' ? 'destructive' : 'default'}
                            onClick={confirm?.kind === 'cancel' ? doCancel : doAdvance}
                        >
                            Confirmer
                        </Button>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
