import VehicleConditionDiagram from '@/Components/VehicleConditionDiagram';
import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

const BADGE = {
    gray: 'bg-muted text-foreground',
    blue: 'bg-blue-100 text-blue-700',
    yellow: 'bg-yellow-100 text-yellow-800',
    orange: 'bg-orange-100 text-orange-700',
    purple: 'bg-purple-100 text-purple-700',
    cyan: 'bg-cyan-100 text-cyan-700',
    green: 'bg-green-100 text-green-700',
    red: 'bg-red-100 text-red-700',
};

function Field({ label, value }) {
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

export default function Show({ order }) {
    const hasCondition =
        order.reception_condition && Object.keys(order.reception_condition).length > 0;

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
                    <Button variant="outline" asChild>
                        <Link href={route('workshop.index')}>← Atelier</Link>
                    </Button>
                </div>
            }
        >
            <Head title={`Ordre ${order.number}`} />

            <div className="py-8">
                <div className="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <div className="border-b border-border px-6 py-4">
                            <h3 className="text-sm font-semibold text-foreground">Réception</h3>
                        </div>
                        <div className="grid grid-cols-2 gap-6 px-6 py-4 sm:grid-cols-4">
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
                        {order.internal_notes && (
                            <div className="border-t border-border px-6 py-4">
                                <Field label="Notes internes" value={order.internal_notes} />
                            </div>
                        )}
                    </div>

                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <div className="border-b border-border px-6 py-4">
                            <h3 className="text-sm font-semibold text-foreground">
                                État du véhicule à la réception
                            </h3>
                        </div>
                        <div className="p-6">
                            {hasCondition ? (
                                <VehicleConditionDiagram
                                    value={order.reception_condition}
                                    readOnly
                                />
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    Aucun dommage marqué à la réception.
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <div className="border-b border-border px-6 py-4">
                            <h3 className="text-sm font-semibold text-foreground">
                                Photos du véhicule
                            </h3>
                        </div>
                        <div className="p-6">
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
                        </div>
                    </div>

                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <div className="border-b border-border px-6 py-4">
                            <h3 className="text-sm font-semibold text-foreground">
                                Signature du client
                            </h3>
                        </div>
                        <div className="p-6">
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
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
