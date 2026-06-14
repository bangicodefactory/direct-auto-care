import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import { Link } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

const FIELD_ORDER = ['first_name', 'last_name', 'phone', 'email', 'address', 'notes'];

export default function CustomerForm({
    data,
    setData,
    errors,
    processing,
    onSubmit,
    cancelHref,
    autoFocus = true,
}) {
    const inputRefs = useRef({});

    useEffect(() => {
        const firstErrorField = FIELD_ORDER.find((field) => errors[field]);
        if (firstErrorField) {
            inputRefs.current[firstErrorField]?.focus();
        }
    }, [errors]);

    return (
        <form onSubmit={onSubmit} className="space-y-6 p-6">
            <div className="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel htmlFor="first_name" value="Prénom" />
                    <TextInput
                        id="first_name"
                        ref={(el) => (inputRefs.current.first_name = el)}
                        value={data.first_name}
                        className="mt-1 block w-full"
                        isFocused={autoFocus}
                        onChange={(e) => setData('first_name', e.target.value)}
                    />
                    <InputError message={errors.first_name} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="last_name" value="Nom de famille" />
                    <TextInput
                        id="last_name"
                        ref={(el) => (inputRefs.current.last_name = el)}
                        value={data.last_name}
                        className="mt-1 block w-full"
                        onChange={(e) => setData('last_name', e.target.value)}
                    />
                    <InputError message={errors.last_name} className="mt-2" />
                </div>
            </div>

            <div>
                <InputLabel htmlFor="phone" value="Téléphone" />
                <TextInput
                    id="phone"
                    ref={(el) => (inputRefs.current.phone = el)}
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
                    ref={(el) => (inputRefs.current.email = el)}
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
                    ref={(el) => (inputRefs.current.address = el)}
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
                    ref={(el) => (inputRefs.current.notes = el)}
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

            <div className="flex items-center justify-end gap-4">
                <Button variant="ghost" asChild>
                    <Link href={cancelHref}>Annuler</Link>
                </Button>
                <Button disabled={processing}>Enregistrer</Button>
            </div>
        </form>
    );
}
