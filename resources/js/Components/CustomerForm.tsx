import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import type { CustomerType } from '@/types/models';
import { Link } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

// Order used to focus the first field with a validation error.
const FIELD_ORDER = [
    'company_name',
    'first_name',
    'last_name',
    'ice',
    'if',
    'patente',
    'ville',
    'phone',
    'email',
    'address',
    'notes',
];

interface CustomerFormData {
    type: CustomerType;
    company_name: string;
    ice: string;
    if: string;
    patente: string;
    ville: string;
    first_name: string;
    last_name: string;
    phone: string;
    email: string;
    address: string;
    notes: string;
    notify_whatsapp: boolean;
    notify_email: boolean;
    fleet_notes_enabled?: boolean;
}

interface CustomerFormProps {
    data: CustomerFormData;
    setData: (key: string, value: string | boolean) => void;
    errors: Record<string, string>;
    processing: boolean;
    onSubmit: (e: React.FormEvent) => void;
    cancelHref: string;
    autoFocus?: boolean;
    /** Admin-only: show the "Notes flotte" toggle (enforced server-side too). */
    showFleetNotesToggle?: boolean;
}

const TYPE_OPTIONS: { value: CustomerType; label: string }[] = [
    { value: 'individual', label: 'Particulier' },
    { value: 'company', label: 'Entreprise' },
];

export default function CustomerForm({
    data,
    setData,
    errors,
    processing,
    onSubmit,
    cancelHref,
    autoFocus = true,
    showFleetNotesToggle = false,
}: CustomerFormProps) {
    const inputRefs = useRef<Record<string, HTMLInputElement | HTMLTextAreaElement | null>>({});
    const isCompany = data.type === 'company';

    useEffect(() => {
        const firstErrorField = FIELD_ORDER.find((field) => errors[field]);
        if (firstErrorField) {
            inputRefs.current[firstErrorField]?.focus();
        }
    }, [errors]);

    return (
        <form onSubmit={onSubmit} className="space-y-6 p-6">
            {/* TYPE TOGGLE */}
            <div>
                <InputLabel value="Type de client" />
                <div
                    className="mt-1 inline-flex rounded-lg border border-border p-1"
                    role="group"
                    aria-label="Type de client"
                >
                    {TYPE_OPTIONS.map((opt) => (
                        <button
                            key={opt.value}
                            type="button"
                            aria-pressed={data.type === opt.value}
                            onClick={() => setData('type', opt.value)}
                            className={`min-h-[40px] rounded-md px-4 text-sm font-medium transition ${
                                data.type === opt.value
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            {opt.label}
                        </button>
                    ))}
                </div>
            </div>

            {/* COMPANY IDENTITY */}
            {isCompany && (
                <>
                    <div>
                        <InputLabel htmlFor="company_name" value="Raison sociale *" />
                        <TextInput
                            id="company_name"
                            ref={(el) => {
                                inputRefs.current.company_name = el;
                            }}
                            value={data.company_name}
                            className="mt-1 block w-full"
                            isFocused={autoFocus}
                            onChange={(e) => setData('company_name', e.target.value)}
                        />
                        <InputError message={errors.company_name} className="mt-2" />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel htmlFor="ice" value="ICE" />
                            <TextInput
                                id="ice"
                                ref={(el) => {
                                    inputRefs.current.ice = el;
                                }}
                                value={data.ice}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('ice', e.target.value)}
                            />
                            <InputError message={errors.ice} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="if" value="IF" />
                            <TextInput
                                id="if"
                                ref={(el) => {
                                    inputRefs.current.if = el;
                                }}
                                value={data.if}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('if', e.target.value)}
                            />
                            <InputError message={errors.if} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="patente" value="Patente" />
                            <TextInput
                                id="patente"
                                ref={(el) => {
                                    inputRefs.current.patente = el;
                                }}
                                value={data.patente}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('patente', e.target.value)}
                            />
                            <InputError message={errors.patente} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="ville" value="Ville" />
                            <TextInput
                                id="ville"
                                ref={(el) => {
                                    inputRefs.current.ville = el;
                                }}
                                value={data.ville}
                                className="mt-1 block w-full"
                                onChange={(e) => setData('ville', e.target.value)}
                            />
                            <InputError message={errors.ville} className="mt-2" />
                        </div>
                    </div>
                </>
            )}

            {/* PERSON NAME — the client for individuals, the contact for companies */}
            <fieldset>
                {isCompany && (
                    <legend className="mb-2 text-sm font-medium text-foreground">
                        Personne à contacter (facultatif)
                    </legend>
                )}
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel
                            htmlFor="first_name"
                            value={isCompany ? 'Prénom' : 'Prénom *'}
                        />
                        <TextInput
                            id="first_name"
                            ref={(el) => {
                                inputRefs.current.first_name = el;
                            }}
                            value={data.first_name}
                            className="mt-1 block w-full"
                            isFocused={autoFocus && !isCompany}
                            onChange={(e) => setData('first_name', e.target.value)}
                        />
                        <InputError message={errors.first_name} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="last_name"
                            value={isCompany ? 'Nom de famille' : 'Nom de famille *'}
                        />
                        <TextInput
                            id="last_name"
                            ref={(el) => {
                                inputRefs.current.last_name = el;
                            }}
                            value={data.last_name}
                            className="mt-1 block w-full"
                            onChange={(e) => setData('last_name', e.target.value)}
                        />
                        <InputError message={errors.last_name} className="mt-2" />
                    </div>
                </div>
            </fieldset>

            <div>
                <InputLabel htmlFor="phone" value="Téléphone *" />
                <TextInput
                    id="phone"
                    ref={(el) => {
                        inputRefs.current.phone = el;
                    }}
                    value={data.phone}
                    className="mt-1 block w-full"
                    placeholder="0612345678"
                    onChange={(e) => setData('phone', e.target.value)}
                />
                <InputError message={errors.phone} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="email" value="Adresse e-mail" />
                <TextInput
                    id="email"
                    type="email"
                    ref={(el) => {
                        inputRefs.current.email = el;
                    }}
                    value={data.email}
                    className="mt-1 block w-full"
                    onChange={(e) => setData('email', e.target.value)}
                />
                <InputError message={errors.email} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="address" value="Adresse" />
                <TextInput
                    id="address"
                    ref={(el) => {
                        inputRefs.current.address = el;
                    }}
                    value={data.address}
                    className="mt-1 block w-full"
                    onChange={(e) => setData('address', e.target.value)}
                />
                <InputError message={errors.address} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="notes" value="Notes" />
                <Textarea
                    id="notes"
                    ref={(el) => {
                        inputRefs.current.notes = el;
                    }}
                    value={data.notes}
                    rows={3}
                    className="mt-1"
                    onChange={(e) => setData('notes', e.target.value)}
                />
                <InputError message={errors.notes} className="mt-2" />
            </div>

            <fieldset>
                <legend className="text-sm font-medium text-foreground">Notifications</legend>
                <div className="mt-2 space-y-2">
                    <label className="flex items-center gap-2">
                        <Checkbox
                            id="notify_whatsapp"
                            checked={data.notify_whatsapp}
                            onChange={(e) => setData('notify_whatsapp', e.target.checked)}
                        />
                        <span className="text-sm text-foreground">Rappels WhatsApp</span>
                    </label>
                    <label className="flex items-center gap-2">
                        <Checkbox
                            id="notify_email"
                            checked={data.notify_email}
                            onChange={(e) => setData('notify_email', e.target.checked)}
                        />
                        <span className="text-sm text-foreground">Rappels par e-mail</span>
                    </label>
                </div>
            </fieldset>

            {showFleetNotesToggle && (
                <fieldset>
                    <legend className="text-sm font-medium text-foreground">Flotte</legend>
                    <label className="mt-2 flex items-start gap-2">
                        <Checkbox
                            id="fleet_notes_enabled"
                            className="mt-0.5"
                            checked={data.fleet_notes_enabled ?? false}
                            onChange={(e) => setData('fleet_notes_enabled', e.target.checked)}
                        />
                        <span className="text-sm text-foreground">
                            Client flotte — activer les notes par véhicule
                            <span className="block text-xs text-muted-foreground">
                                Ses véhicules apparaissent dans le menu « Notes flotte ».
                            </span>
                        </span>
                    </label>
                    <InputError message={errors.fleet_notes_enabled} className="mt-2" />
                </fieldset>
            )}

            <div className="flex items-center justify-end gap-4">
                <Button variant="ghost" asChild>
                    <Link href={cancelHref}>Annuler</Link>
                </Button>
                <Button disabled={processing}>Enregistrer</Button>
            </div>
        </form>
    );
}
