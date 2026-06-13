import CustomerCombobox from '@/Components/CustomerCombobox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import VehicleConditionDiagram from '@/Components/VehicleConditionDiagram';
import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo } from 'react';

const FIELD =
    'mt-1 block min-h-[44px] w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary';

function Card({ title, description, children }) {
    return (
        <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
            <div className="p-6">
                <h3 className="text-base font-semibold text-foreground">{title}</h3>
                {description && (
                    <p className="mb-4 mt-1 text-sm text-muted-foreground">{description}</p>
                )}
                <div className={description ? '' : 'mt-4'}>{children}</div>
            </div>
        </div>
    );
}

export default function Create({ customers, vehicles, todayAppointments }) {
    const { data, setData, post, processing, errors } = useForm({
        customer_id: '',
        vehicle_id: '',
        appointment_id: '',
        mileage_in: '',
        reception_condition: {},
        internal_notes: '',
    });

    const customerVehicles = useMemo(
        () => vehicles.filter((v) => String(v.customer_id) === String(data.customer_id)),
        [vehicles, data.customer_id],
    );

    const customerAppointments = useMemo(
        () => todayAppointments.filter((a) => String(a.customer_id) === String(data.customer_id)),
        [todayAppointments, data.customer_id],
    );

    function handleCustomer(id) {
        setData((prev) => ({ ...prev, customer_id: id, vehicle_id: '', appointment_id: '' }));
    }

    function submit(e) {
        e.preventDefault();
        post(route('workshop.store'));
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-foreground">
                        Réception d'un véhicule
                    </h2>
                    <Link
                        href={route('workshop.index')}
                        className="text-sm text-muted-foreground hover:text-foreground"
                    >
                        ← Atelier
                    </Link>
                </div>
            }
        >
            <Head title="Réception d'un véhicule" />

            <div className="py-8">
                <form
                    onSubmit={submit}
                    className="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8"
                >
                    <Card title="Client & véhicule">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel htmlFor="customer_id" value="Client *" />
                                <CustomerCombobox
                                    customers={customers}
                                    value={data.customer_id}
                                    onChange={handleCustomer}
                                    error={errors.customer_id}
                                />
                                <InputError message={errors.customer_id} className="mt-1" />
                            </div>

                            <div>
                                <InputLabel htmlFor="vehicle_id" value="Véhicule *" />
                                <select
                                    id="vehicle_id"
                                    className={FIELD}
                                    value={data.vehicle_id}
                                    disabled={!data.customer_id}
                                    onChange={(e) => setData('vehicle_id', e.target.value)}
                                >
                                    <option value="">— Sélectionner un véhicule —</option>
                                    {customerVehicles.map((v) => (
                                        <option key={v.id} value={v.id}>
                                            {v.license_plate} — {v.make} {v.model}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.vehicle_id} className="mt-1" />
                            </div>

                            <div>
                                <InputLabel htmlFor="mileage_in" value="Kilométrage à l'entrée" />
                                <TextInput
                                    id="mileage_in"
                                    type="number"
                                    min="0"
                                    className="mt-1 block w-full"
                                    value={data.mileage_in}
                                    onChange={(e) => setData('mileage_in', e.target.value)}
                                    placeholder="ex. 84500"
                                />
                                <InputError message={errors.mileage_in} className="mt-1" />
                            </div>

                            {customerAppointments.length > 0 && (
                                <div>
                                    <InputLabel
                                        htmlFor="appointment_id"
                                        value="Rendez-vous du jour (optionnel)"
                                    />
                                    <select
                                        id="appointment_id"
                                        className={FIELD}
                                        value={data.appointment_id}
                                        onChange={(e) => setData('appointment_id', e.target.value)}
                                    >
                                        <option value="">— Aucun —</option>
                                        {customerAppointments.map((a) => (
                                            <option key={a.id} value={a.id}>
                                                {a.time}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.appointment_id} className="mt-1" />
                                </div>
                            )}
                        </div>
                    </Card>

                    <Card
                        title="État du véhicule à la réception"
                        description="Cliquez sur les parties endommagées pour les marquer."
                    >
                        <VehicleConditionDiagram
                            value={data.reception_condition}
                            onChange={(map) => setData('reception_condition', map)}
                        />
                        <InputError message={errors.reception_condition} className="mt-2" />
                    </Card>

                    <Card title="Notes internes">
                        <textarea
                            id="internal_notes"
                            rows={3}
                            className={FIELD}
                            value={data.internal_notes}
                            onChange={(e) => setData('internal_notes', e.target.value)}
                            placeholder="Observations, demandes du client…"
                        />
                        <InputError message={errors.internal_notes} className="mt-1" />
                    </Card>

                    <div className="flex items-center justify-end gap-3">
                        <Button variant="outline" asChild>
                            <Link href={route('workshop.index')}>Annuler</Link>
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing || !data.customer_id || !data.vehicle_id}
                        >
                            {processing ? 'Création…' : 'Créer la fiche de réception'}
                        </Button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
