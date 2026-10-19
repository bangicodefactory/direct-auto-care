import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';

interface FleetVehicle {
    id: number;
    license_plate: string;
    make: string;
    model: string;
    year: number | null;
    customer: { id: number; display_name: string };
    open_order: { id: number; number: string; status: string } | null;
}

interface FleetNote {
    id: number;
    body: string;
    author: string;
    created_at: string;
    edited: boolean;
    can: { update: boolean; delete: boolean };
}

interface ShowProps {
    vehicle: FleetVehicle;
    notes: FleetNote[];
    can: { create: boolean };
}

function NoteItem({ note, onDelete }: { note: FleetNote; onDelete: (note: FleetNote) => void }) {
    const [editing, setEditing] = useState(false);
    const { data, setData, put, processing, errors, reset, clearErrors } = useForm({
        body: note.body,
    });

    function save(e: React.FormEvent) {
        e.preventDefault();
        put(route('vehicle-notes.update', note.id), {
            preserveScroll: true,
            onSuccess: () => setEditing(false),
        });
    }

    function cancel() {
        reset();
        clearErrors();
        setEditing(false);
    }

    return (
        <li className="p-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <p className="text-xs text-muted-foreground">
                    <span className="font-medium text-foreground">{note.author}</span> ·{' '}
                    {note.created_at}
                    {note.edited && ' · modifiée'}
                </p>
                {!editing && (note.can.update || note.can.delete) && (
                    <div className="flex gap-1">
                        {note.can.update && (
                            <Button variant="ghost" size="sm" onClick={() => setEditing(true)}>
                                Modifier
                            </Button>
                        )}
                        {note.can.delete && (
                            <Button
                                variant="ghost"
                                size="sm"
                                className="text-destructive hover:text-destructive"
                                onClick={() => onDelete(note)}
                            >
                                Supprimer
                            </Button>
                        )}
                    </div>
                )}
            </div>

            {editing ? (
                <form onSubmit={save} className="mt-2 space-y-2">
                    <Textarea
                        value={data.body}
                        rows={4}
                        aria-label="Modifier la note"
                        onChange={(e) => setData('body', e.target.value)}
                    />
                    <InputError message={errors.body} />
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="ghost" onClick={cancel}>
                            Annuler
                        </Button>
                        <Button disabled={processing}>Enregistrer</Button>
                    </div>
                </form>
            ) : (
                <p className="mt-2 whitespace-pre-wrap break-words text-sm text-foreground">
                    {note.body}
                </p>
            )}
        </li>
    );
}

export default function Show({ vehicle, notes, can }: ShowProps) {
    const [deletingNote, setDeletingNote] = useState<FleetNote | null>(null);
    const [deleting, setDeleting] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({ body: '' });

    const title = `${vehicle.license_plate} — ${vehicle.make} ${vehicle.model}`;

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(route('fleet-notes.notes.store', vehicle.id), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    }

    function destroy() {
        setDeleting(true);
        router.delete(route('vehicle-notes.destroy', deletingNote!.id), {
            preserveScroll: true,
            onFinish: () => {
                setDeleting(false);
                setDeletingNote(null);
            },
        });
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex min-w-0 items-center gap-3">
                    <Link
                        href={route('fleet-notes.index')}
                        className="flex min-h-[44px] min-w-[44px] items-center justify-center rounded text-muted-foreground hover:bg-accent hover:text-foreground"
                        aria-label="Retour aux notes flotte"
                    >
                        <ArrowLeft className="h-5 w-5 rtl:rotate-180" />
                    </Link>
                    <h2 className="min-w-0 truncate text-xl font-semibold leading-tight text-foreground">
                        {title}
                    </h2>
                </div>
            }
        >
            <Head title={`Notes — ${vehicle.license_plate}`} />

            <div className="py-8">
                <div className="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                    {/* VEHICLE */}
                    <div className="rounded-lg border border-border bg-card p-4 shadow-sm sm:p-6">
                        <dl className="grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="font-medium text-muted-foreground">Client</dt>
                                <dd>
                                    <Link
                                        href={route('customers.show', vehicle.customer.id)}
                                        className="text-primary hover:underline"
                                    >
                                        {vehicle.customer.display_name}
                                    </Link>
                                </dd>
                            </div>
                            <div>
                                <dt className="font-medium text-muted-foreground">Véhicule</dt>
                                <dd>
                                    <Link
                                        href={route('vehicles.show', vehicle.id)}
                                        className="text-primary hover:underline"
                                    >
                                        {vehicle.make} {vehicle.model}
                                        {vehicle.year ? ` (${vehicle.year})` : ''}
                                    </Link>
                                </dd>
                            </div>
                            <div className="sm:col-span-2">
                                <dt className="font-medium text-muted-foreground">Atelier</dt>
                                <dd className="text-foreground">
                                    {vehicle.open_order ? (
                                        <Link
                                            href={route('workshop.show', vehicle.open_order.id)}
                                            className="text-primary hover:underline"
                                        >
                                            {vehicle.open_order.number} —{' '}
                                            {vehicle.open_order.status}
                                        </Link>
                                    ) : (
                                        'Pas à l’atelier actuellement'
                                    )}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    {/* ADD NOTE */}
                    {can.create && (
                        <form
                            onSubmit={submit}
                            className="space-y-3 rounded-lg border border-border bg-card p-4 shadow-sm sm:p-6"
                        >
                            <InputLabel htmlFor="body" value="Ajouter une note" />
                            <Textarea
                                id="body"
                                value={data.body}
                                rows={3}
                                placeholder="Ex. : le chauffeur signale un bruit au freinage…"
                                onChange={(e) => setData('body', e.target.value)}
                            />
                            <InputError message={errors.body} />
                            <div className="flex justify-end">
                                <Button disabled={processing || data.body.trim() === ''}>
                                    Ajouter
                                </Button>
                            </div>
                        </form>
                    )}

                    {/* TIMELINE */}
                    <div className="overflow-hidden rounded-lg border border-border bg-card shadow-sm">
                        <h3 className="border-b border-border px-4 py-3 text-sm font-medium text-foreground sm:px-6">
                            Historique des notes ({notes.length})
                        </h3>
                        {notes.length === 0 ? (
                            <p className="p-6 text-sm text-muted-foreground">
                                Aucune note pour ce véhicule.
                            </p>
                        ) : (
                            <ul className="divide-y divide-border">
                                {notes.map((note) => (
                                    <NoteItem
                                        key={note.id}
                                        note={note}
                                        onDelete={setDeletingNote}
                                    />
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </div>

            <Modal show={deletingNote !== null} maxWidth="sm" onClose={() => setDeletingNote(null)}>
                <div className="p-6">
                    <h2 className="text-lg font-medium text-foreground">Supprimer cette note ?</h2>
                    <p className="mt-1 text-sm text-muted-foreground">
                        La note sera définitivement supprimée de l&apos;historique du véhicule.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <Button variant="secondary" onClick={() => setDeletingNote(null)}>
                            Annuler
                        </Button>
                        <Button variant="destructive" onClick={destroy} disabled={deleting}>
                            Supprimer
                        </Button>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
