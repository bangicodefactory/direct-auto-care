import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import ServiceTypeMultiCheck from '@/Components/ServiceTypeMultiCheck';
import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { ServiceType, Vehicle } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

interface CreateProps {
    vehicle: Pick<Vehicle, 'id' | 'make' | 'model' | 'license_plate' | 'mileage'>;
    serviceTypes: Pick<ServiceType, 'id' | 'name'>[];
    prefill: {
        serviced_at: string;
        service_type_ids: number[];
    } | null;
}

export default function Create({ vehicle, serviceTypes, prefill }: CreateProps) {
    const today = new Date().toISOString().split('T')[0];

    const { data, setData, post, processing, errors } = useForm({
        service_type_ids: prefill?.service_type_ids ?? [],
        serviced_at: prefill?.serviced_at ?? today,
        mileage_at_service: '',
        notes: '',
    });

    function submit(e: FormEvent<HTMLFormElement>) {
        e.preventDefault();
        post(route('vehicles.service-records.store', vehicle.id));
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-foreground">
                    Nouveau compte rendu —{' '}
                    <span className="font-normal text-muted-foreground">
                        {vehicle.make} {vehicle.model} ({vehicle.license_plate})
                    </span>
                </h2>
            }
        >
            <Head title="Nouveau compte rendu" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <form onSubmit={submit} className="space-y-6 p-6">
                            <div>
                                <InputLabel htmlFor="serviced_at" value="Date du service" />
                                <TextInput
                                    id="serviced_at"
                                    type="date"
                                    value={data.serviced_at}
                                    max={today}
                                    isFocused
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('serviced_at', e.target.value)}
                                />
                                <InputError message={errors.serviced_at} className="mt-2" />
                            </div>

                            <ServiceTypeMultiCheck
                                serviceTypes={serviceTypes}
                                selected={data.service_type_ids}
                                onChange={(ids) => setData('service_type_ids', ids)}
                                error={errors.service_type_ids}
                                disabled={processing}
                            />

                            <div>
                                <InputLabel
                                    htmlFor="mileage_at_service"
                                    value="Kilométrage au moment du service (km)"
                                />
                                <TextInput
                                    id="mileage_at_service"
                                    type="number"
                                    value={data.mileage_at_service}
                                    min={vehicle.mileage ?? 0}
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('mileage_at_service', e.target.value)}
                                />
                                {vehicle.mileage != null && (
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Minimum&nbsp;: {vehicle.mileage.toLocaleString('fr-MA')} km
                                    </p>
                                )}
                                <InputError message={errors.mileage_at_service} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="notes" value="Notes" />
                                <Textarea
                                    id="notes"
                                    value={data.notes}
                                    rows={4}
                                    className="mt-1"
                                    onChange={(e) => setData('notes', e.target.value)}
                                />
                                <InputError message={errors.notes} className="mt-2" />
                            </div>

                            <div className="flex items-center justify-end gap-4">
                                <Link
                                    href={route('vehicles.show', vehicle.id)}
                                    className="text-sm text-muted-foreground hover:text-foreground"
                                >
                                    Annuler
                                </Link>
                                <Button disabled={processing}>Enregistrer</Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
