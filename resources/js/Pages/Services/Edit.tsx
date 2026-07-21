import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

interface EditProps {
    service: {
        id: number;
        name: string;
        description: string | null;
        default_price_ht: string;
        is_active: boolean;
    };
}

export default function Edit({ service }: EditProps) {
    const { data, setData, put, processing, errors } = useForm<{
        name: string;
        description: string;
        default_price_ht: string;
        is_active: boolean;
    }>({
        name: service.name,
        description: service.description ?? '',
        default_price_ht: String(service.default_price_ht),
        is_active: service.is_active,
    });

    function submit(e: FormEvent) {
        e.preventDefault();
        put(route('services.update', service.id));
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-foreground">
                    Modifier le service
                </h2>
            }
        >
            <Head title={`Modifier — ${service.name}`} />

            <div className="py-8">
                <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                    <form
                        onSubmit={submit}
                        className="space-y-5 rounded-lg border border-border bg-card p-6 shadow-sm"
                    >
                        <div>
                            <InputLabel htmlFor="name" value="Nom *" />
                            <TextInput
                                id="name"
                                className="mt-1 block w-full"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                            />
                            <InputError message={errors.name} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="description" value="Description" />
                            <TextInput
                                id="description"
                                className="mt-1 block w-full"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                            />
                            <InputError message={errors.description} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel
                                htmlFor="default_price_ht"
                                value="Prix par défaut HT (DH) *"
                            />
                            <TextInput
                                id="default_price_ht"
                                type="number"
                                step="0.01"
                                min="0"
                                className="mt-1 block w-full"
                                value={data.default_price_ht}
                                onChange={(e) => setData('default_price_ht', e.target.value)}
                            />
                            <p className="mt-1 text-xs text-muted-foreground">
                                Prix proposé par défaut — modifiable sur chaque ligne selon le
                                véhicule.
                            </p>
                            <InputError message={errors.default_price_ht} className="mt-1" />
                        </div>

                        <div>
                            <label className="flex items-center gap-2">
                                <Checkbox
                                    id="is_active"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                />
                                <span className="text-sm text-muted-foreground">
                                    Service actif (proposé dans les documents)
                                </span>
                            </label>
                            <InputError message={errors.is_active} className="mt-1" />
                        </div>

                        <div className="flex items-center justify-between pt-2">
                            <Link
                                href={route('services.index')}
                                className="text-sm text-muted-foreground hover:underline"
                            >
                                Annuler
                            </Link>
                            <Button disabled={processing}>Enregistrer</Button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
