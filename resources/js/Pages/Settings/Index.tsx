import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Settings } from '@/types/models';
import { Head, useForm } from '@inertiajs/react';

/** Brand color form fields — `name` keys a string field on the settings form. */
type ColorField =
    | 'color_primary'
    | 'color_accent'
    | 'color_bg'
    | 'color_text_primary'
    | 'color_text_muted'
    | 'color_success'
    | 'color_warning'
    | 'color_error';

const COLOR_FIELDS: { name: ColorField; label: string }[] = [
    { name: 'color_primary', label: 'Couleur principale' },
    { name: 'color_accent', label: 'Couleur accent' },
    { name: 'color_bg', label: 'Arrière-plan' },
    { name: 'color_text_primary', label: 'Texte principal' },
    { name: 'color_text_muted', label: 'Texte secondaire' },
    { name: 'color_success', label: 'Succès' },
    { name: 'color_warning', label: 'Avertissement' },
    { name: 'color_error', label: 'Erreur' },
];

/**
 * `SettingsController@edit` projection: the `only([...])` subset of the Settings
 * model plus the computed `logo_url`.
 */
type SettingsPayload = Pick<
    Settings,
    | 'company_name'
    | 'company_address'
    | 'company_ice'
    | 'company_if'
    | 'company_rc'
    | 'tva_rate'
    | 'color_primary'
    | 'color_accent'
    | 'color_bg'
    | 'color_text_primary'
    | 'color_text_muted'
    | 'color_success'
    | 'color_warning'
    | 'color_error'
> & { logo_url: string | null };

interface SettingsForm {
    company_name: string;
    company_address: string;
    company_ice: string;
    company_if: string;
    company_rc: string;
    tva_rate: number | string;
    logo: File | null;
    color_primary: string;
    color_accent: string;
    color_bg: string;
    color_text_primary: string;
    color_text_muted: string;
    color_success: string;
    color_warning: string;
    color_error: string;
}

interface IndexProps {
    settings: SettingsPayload;
}

export default function Index({ settings }: IndexProps) {
    const { data, setData, post, processing, errors, progress } = useForm<SettingsForm>({
        company_name: settings.company_name ?? '',
        company_address: settings.company_address ?? '',
        company_ice: settings.company_ice ?? '',
        company_if: settings.company_if ?? '',
        company_rc: settings.company_rc ?? '',
        tva_rate: settings.tva_rate ?? 20,
        logo: null,
        color_primary: settings.color_primary ?? '#0B3D91',
        color_accent: settings.color_accent ?? '#E63946',
        color_bg: settings.color_bg ?? '#F8FAFC',
        color_text_primary: settings.color_text_primary ?? '#0F172A',
        color_text_muted: settings.color_text_muted ?? '#64748B',
        color_success: settings.color_success ?? '#10B981',
        color_warning: settings.color_warning ?? '#F59E0B',
        color_error: settings.color_error ?? '#EF4444',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(route('settings.update'), { forceFormData: true });
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-foreground">Paramètres</h2>
            }
        >
            <Head title="Paramètres" />

            <div className="py-8">
                <div className="mx-auto max-w-3xl space-y-8 px-4 sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="space-y-8">
                        {/* ── Company info ── */}
                        <section className="rounded-lg border border-border bg-card p-6 shadow-sm">
                            <h3 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                Informations de l&apos;entreprise
                            </h3>

                            <div className="space-y-4">
                                <div>
                                    <InputLabel htmlFor="company_name" value="Raison sociale" />
                                    <TextInput
                                        id="company_name"
                                        className="mt-1 block w-full"
                                        value={data.company_name}
                                        onChange={(e) => setData('company_name', e.target.value)}
                                    />
                                    <InputError message={errors.company_name} className="mt-1" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="company_address" value="Adresse" />
                                    <Textarea
                                        id="company_address"
                                        rows={3}
                                        className="mt-1"
                                        value={data.company_address}
                                        onChange={(e) => setData('company_address', e.target.value)}
                                    />
                                    <InputError message={errors.company_address} className="mt-1" />
                                </div>

                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div>
                                        <InputLabel htmlFor="company_ice" value="ICE" />
                                        <TextInput
                                            id="company_ice"
                                            className="mt-1 block w-full"
                                            value={data.company_ice}
                                            onChange={(e) => setData('company_ice', e.target.value)}
                                        />
                                        <InputError message={errors.company_ice} className="mt-1" />
                                    </div>
                                    <div>
                                        <InputLabel
                                            htmlFor="company_if"
                                            value="Identifiant Fiscal"
                                        />
                                        <TextInput
                                            id="company_if"
                                            className="mt-1 block w-full"
                                            value={data.company_if}
                                            onChange={(e) => setData('company_if', e.target.value)}
                                        />
                                        <InputError message={errors.company_if} className="mt-1" />
                                    </div>
                                    <div>
                                        <InputLabel
                                            htmlFor="company_rc"
                                            value="Registre de commerce"
                                        />
                                        <TextInput
                                            id="company_rc"
                                            className="mt-1 block w-full"
                                            value={data.company_rc}
                                            onChange={(e) => setData('company_rc', e.target.value)}
                                        />
                                        <InputError message={errors.company_rc} className="mt-1" />
                                    </div>
                                </div>

                                <div className="max-w-[160px]">
                                    <InputLabel
                                        htmlFor="tva_rate"
                                        value="Taux TVA par défaut (%)"
                                    />
                                    <TextInput
                                        id="tva_rate"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        className="mt-1 block w-full"
                                        value={data.tva_rate}
                                        onChange={(e) => setData('tva_rate', e.target.value)}
                                    />
                                    <InputError message={errors.tva_rate} className="mt-1" />
                                </div>
                            </div>
                        </section>

                        {/* ── Logo ── */}
                        <section className="rounded-lg border border-border bg-card p-6 shadow-sm">
                            <h3 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                Logo
                            </h3>

                            <div className="flex items-start gap-6">
                                <div className="flex h-20 w-20 shrink-0 items-center justify-center rounded-lg border border-border bg-muted">
                                    {settings.logo_url ? (
                                        <img
                                            src={settings.logo_url}
                                            alt="Logo actuel"
                                            className="max-h-full max-w-full object-contain"
                                        />
                                    ) : (
                                        <span className="text-xs text-muted-foreground">Aucun</span>
                                    )}
                                </div>

                                <div className="flex-1">
                                    <InputLabel htmlFor="logo" value="Nouveau logo" />
                                    <input
                                        id="logo"
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.svg,.webp"
                                        className="mt-1 block w-full text-sm text-muted-foreground ltr:file:mr-3 rtl:file:ml-3 file:rounded file:border-0 file:bg-muted file:px-3 file:py-1 file:text-sm file:font-medium hover:file:bg-muted/80"
                                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                                            setData('logo', e.target.files?.[0] ?? null)
                                        }
                                    />
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        JPG, PNG, SVG ou WebP — max 2 Mo
                                    </p>
                                    <InputError message={errors.logo} className="mt-1" />
                                    {progress && (
                                        <progress
                                            className="mt-2 w-full"
                                            value={progress.percentage}
                                            max="100"
                                        >
                                            {progress.percentage}%
                                        </progress>
                                    )}
                                </div>
                            </div>
                        </section>

                        {/* ── Brand colors ── */}
                        <section className="rounded-lg border border-border bg-card p-6 shadow-sm">
                            <h3 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                Couleurs de la marque
                            </h3>

                            <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                                {COLOR_FIELDS.map(({ name, label }) => (
                                    <div key={name}>
                                        <InputLabel htmlFor={name} value={label} />
                                        <div className="mt-1 flex items-center gap-2">
                                            <input
                                                id={name}
                                                type="color"
                                                value={data[name]}
                                                onChange={(e) => setData(name, e.target.value)}
                                                className="h-9 w-9 shrink-0 cursor-pointer rounded border border-border p-0.5"
                                            />
                                            <TextInput
                                                value={data[name]}
                                                onChange={(e) => setData(name, e.target.value)}
                                                className="block w-full font-mono text-xs uppercase"
                                                maxLength={7}
                                            />
                                        </div>
                                        <InputError message={errors[name]} className="mt-1" />
                                    </div>
                                ))}
                            </div>
                        </section>

                        <div className="flex justify-end">
                            <Button disabled={processing}>
                                {processing ? 'Enregistrement…' : 'Enregistrer'}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
