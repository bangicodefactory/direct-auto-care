import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { User } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/react';

const roleLabels: Record<string, string> = { admin: 'Administrateur', employee: 'Employé' };

/** `$user->load('roles')` — full User model with eager-loaded Spatie roles. */
interface UserWithRoles extends User {
    roles: { name: string }[];
}

interface EditProps {
    user: UserWithRoles;
    roles: string[];
}

export default function Edit({ user, roles }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        name: user.name,
        email: user.email,
        role: user.roles[0]?.name ?? roles[0] ?? '',
        is_active: user.is_active,
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        put(route('users.update', user.id));
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-foreground">
                    Modifier l&apos;utilisateur
                </h2>
            }
        >
            <Head title="Modifier l'utilisateur" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <form onSubmit={submit} className="space-y-6 p-6">
                            <div>
                                <InputLabel htmlFor="name" value="Nom" />
                                <TextInput
                                    id="name"
                                    value={data.name}
                                    className="mt-1 block w-full"
                                    isFocused
                                    onChange={(e) => setData('name', e.target.value)}
                                />
                                <InputError message={errors.name} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="email" value="Adresse e-mail" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('email', e.target.value)}
                                />
                                <InputError message={errors.email} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="role" value="Rôle" />
                                <Select value={data.role} onValueChange={(v) => setData('role', v)}>
                                    <SelectTrigger id="role" className="mt-1 w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {roles.map((r) => (
                                            <SelectItem key={r} value={r}>
                                                {roleLabels[r] ?? r}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.role} className="mt-2" />
                            </div>

                            <div>
                                <label className="flex items-center gap-2">
                                    <Checkbox
                                        id="is_active"
                                        checked={data.is_active}
                                        onChange={(e) => setData('is_active', e.target.checked)}
                                    />
                                    <span className="text-sm text-muted-foreground">
                                        Compte actif
                                    </span>
                                </label>
                                <InputError message={errors.is_active} className="mt-2" />
                            </div>

                            <div className="flex items-center justify-end gap-4">
                                <Link
                                    href={route('users.index')}
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
