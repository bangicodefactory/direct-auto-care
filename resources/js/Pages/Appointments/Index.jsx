import CustomerCombobox from '@/Components/CustomerCombobox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import ServiceTypeMultiCheck from '@/Components/ServiceTypeMultiCheck';
import TextInput from '@/Components/TextInput';
import { Button } from '@/Components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Deferred, Head, Link, router, useForm } from '@inertiajs/react';
import { format, getDay, parse, startOfWeek } from 'date-fns';
import { fr } from 'date-fns/locale';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { Calendar, Views, dateFnsLocalizer } from 'react-big-calendar';
import _withDragAndDrop from 'react-big-calendar/lib/addons/dragAndDrop';
import 'react-big-calendar/lib/addons/dragAndDrop/styles.css';
import 'react-big-calendar/lib/css/react-big-calendar.css';
import { DndProvider } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';

const localizer = dateFnsLocalizer({
    format,
    parse,
    startOfWeek: (date) => startOfWeek(date, { locale: fr }),
    getDay,
    locales: { fr },
});

const withDragAndDrop = _withDragAndDrop.default ?? _withDragAndDrop;
const DnDCalendar = withDragAndDrop(Calendar);

const STATUS_STYLES = {
    pending: { backgroundColor: '#fef9c3', borderColor: '#ca8a04', color: '#713f12' },
    confirmed: { backgroundColor: '#dbeafe', borderColor: '#2563eb', color: '#1e3a8a' },
    completed: { backgroundColor: '#dcfce7', borderColor: '#16a34a', color: '#14532d' },
    cancelled: { backgroundColor: '#fee2e2', borderColor: '#dc2626', color: '#7f1d1d' },
};

const MESSAGES = {
    allDay: 'Toute la journée',
    previous: '‹',
    next: '›',
    today: "Aujourd'hui",
    month: 'Mois',
    week: 'Semaine',
    day: 'Jour',
    agenda: 'Agenda',
    date: 'Date',
    time: 'Heure',
    event: 'Événement',
    noEventsInRange: 'Aucun rendez-vous sur cette période.',
    showMore: (n) => `+ ${n} de plus`,
};

function toDate(iso) {
    return new Date(iso);
}

function buildReschedulePayload(event, start, end, fallbackDuration = false) {
    const durationMin = Math.round((end - start) / 60000);
    return {
        customer_id: event.customer.id,
        vehicle_id: event.vehicle_id ?? null,
        scheduled_at: format(start, "yyyy-MM-dd'T'HH:mm"),
        duration: Math.max(
            15,
            Math.min(480, durationMin || (fallbackDuration ? event.duration : durationMin)),
        ),
        status: event.status,
        notes: event.notes ?? null,
        service_type_ids: event.service_type_ids ?? [],
    };
}

export default function AppointmentsIndex({
    events,
    statuses,
    customers,
    vehicles,
    serviceTypes,
    can,
}) {
    const [view, setView] = useState(Views.MONTH);
    const [date, setDate] = useState(new Date());
    const [createModal, setCreateModal] = useState(null);

    const calendarEvents = useMemo(
        () => events.map((e) => ({ ...e, start: toDate(e.start), end: toDate(e.end) })),
        [events],
    );

    const eventPropGetter = useCallback((event) => {
        const s = STATUS_STYLES[event.status] ?? {};
        return {
            style: {
                backgroundColor: s.backgroundColor,
                borderLeft: `4px solid ${s.borderColor}`,
                color: s.color,
                borderRadius: '3px',
                fontSize: '0.75rem',
            },
        };
    }, []);

    function onRangeChange(range) {
        let start, end;
        if (Array.isArray(range)) {
            start = range[0];
            end = range[range.length - 1];
        } else {
            start = range.start;
            end = range.end;
        }
        router.get(
            route('appointments.index'),
            { start: format(start, 'yyyy-MM-dd'), end: format(end, 'yyyy-MM-dd') },
            { preserveState: true, replace: true },
        );
    }

    const onSelectSlot = useCallback(
        ({ start }) => {
            if (!can.create) return;
            setCreateModal({ scheduled_at: format(start, "yyyy-MM-dd'T'HH:mm") });
        },
        [can.create],
    );

    const onSelectEvent = useCallback((event) => {
        router.visit(route('appointments.show', event.id));
    }, []);

    const onEventDrop = useCallback(({ event, start, end }) => {
        router.put(
            route('appointments.update', event.id),
            buildReschedulePayload(event, start, end, true),
            { preserveState: false },
        );
    }, []);

    const onEventResize = useCallback(({ event, start, end }) => {
        router.put(
            route('appointments.update', event.id),
            buildReschedulePayload(event, start, end),
            { preserveState: false },
        );
    }, []);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-foreground">
                        Rendez-vous
                    </h2>
                    {can.create && (
                        <Button asChild>
                            <Link href={route('appointments.create')}>+ Nouveau rendez-vous</Link>
                        </Button>
                    )}
                </div>
            }
        >
            <Head title="Rendez-vous" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div
                        className="overflow-hidden rounded-lg border border-border bg-card p-4 shadow-sm"
                        style={{ height: '72vh' }}
                    >
                        <DndProvider backend={HTML5Backend}>
                            <DnDCalendar
                                localizer={localizer}
                                culture="fr"
                                events={calendarEvents}
                                view={view}
                                date={date}
                                onView={setView}
                                onNavigate={setDate}
                                onRangeChange={onRangeChange}
                                onSelectSlot={onSelectSlot}
                                onSelectEvent={onSelectEvent}
                                onEventDrop={onEventDrop}
                                onEventResize={onEventResize}
                                selectable
                                resizable
                                eventPropGetter={eventPropGetter}
                                messages={MESSAGES}
                                views={[Views.MONTH, Views.WEEK, Views.DAY]}
                                style={{ height: '100%' }}
                                popup
                            />
                        </DndProvider>
                    </div>

                    <div className="mt-3 flex flex-wrap gap-2">
                        {statuses.map((s) => {
                            const c = STATUS_STYLES[s.value];
                            return (
                                <span
                                    key={s.value}
                                    className="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    style={{
                                        backgroundColor: c?.backgroundColor,
                                        color: c?.color,
                                        border: `1px solid ${c?.borderColor}`,
                                    }}
                                >
                                    {s.label}
                                </span>
                            );
                        })}
                    </div>
                </div>
            </div>

            {can.create && (
                <Deferred data={['customers', 'vehicles', 'serviceTypes']} fallback={null}>
                    <CreateModal
                        show={createModal !== null}
                        initialData={createModal}
                        customers={customers}
                        vehicles={vehicles}
                        serviceTypes={serviceTypes}
                        onClose={() => setCreateModal(null)}
                    />
                </Deferred>
            )}
        </AuthenticatedLayout>
    );
}

function CreateModal({ show, initialData, customers, vehicles, serviceTypes, onClose }) {
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        customer_id: '',
        vehicle_id: '',
        scheduled_at: '',
        duration: 60,
        service_type_ids: [],
        notes: '',
    });

    useEffect(() => {
        if (show && initialData?.scheduled_at) {
            setData('scheduled_at', initialData.scheduled_at);
        }
    }, [show, initialData?.scheduled_at, setData]);

    const filteredVehicles = useMemo(
        () => vehicles.filter((v) => String(v.customer_id) === String(data.customer_id)),
        [vehicles, data.customer_id],
    );

    function submit(e) {
        e.preventDefault();
        post(route('appointments.store'), {
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    }

    function handleClose() {
        reset();
        clearErrors();
        onClose();
    }

    return (
        <Modal show={show} maxWidth="lg" onClose={handleClose}>
            <form onSubmit={submit} className="p-6">
                <h3 className="mb-4 text-lg font-medium text-foreground">Nouveau rendez-vous</h3>

                <div className="mb-4">
                    <InputLabel value="Client *" />
                    <CustomerCombobox
                        customers={customers}
                        value={data.customer_id}
                        onChange={(id) =>
                            setData((prev) => ({ ...prev, customer_id: id, vehicle_id: '' }))
                        }
                        error={errors.customer_id}
                    />
                </div>

                <div className="mb-4">
                    <InputLabel htmlFor="modal_vehicle_id" value="Véhicule" />
                    <Select
                        value={data.vehicle_id ? String(data.vehicle_id) : '__none__'}
                        onValueChange={(v) => setData('vehicle_id', v === '__none__' ? '' : v)}
                        disabled={!data.customer_id}
                    >
                        <SelectTrigger id="modal_vehicle_id" className="mt-1 min-h-[44px] w-full">
                            <SelectValue placeholder="— Aucun —" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__none__">— Aucun —</SelectItem>
                            {filteredVehicles.map((v) => (
                                <SelectItem key={v.id} value={String(v.id)}>
                                    {v.license_plate} {v.make} {v.model}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.vehicle_id} className="mt-1" />
                </div>

                <div className="mb-4 grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel htmlFor="modal_scheduled_at" value="Date et heure *" />
                        <TextInput
                            id="modal_scheduled_at"
                            type="datetime-local"
                            value={data.scheduled_at}
                            onChange={(e) => setData('scheduled_at', e.target.value)}
                            className="mt-1 block w-full"
                        />
                        <InputError message={errors.scheduled_at} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="modal_duration" value="Durée (min) *" />
                        <TextInput
                            id="modal_duration"
                            type="number"
                            min={15}
                            max={480}
                            step={15}
                            value={data.duration}
                            onChange={(e) => setData('duration', e.target.value)}
                            className="mt-1 block w-full"
                        />
                        <InputError message={errors.duration} className="mt-1" />
                    </div>
                </div>

                <div className="mb-4">
                    <ServiceTypeMultiCheck
                        serviceTypes={serviceTypes}
                        selected={data.service_type_ids}
                        onChange={(ids) => setData('service_type_ids', ids)}
                        error={errors.service_type_ids}
                        label="Types de service"
                    />
                </div>

                <div className="mb-6">
                    <InputLabel htmlFor="modal_notes" value="Notes" />
                    <Textarea
                        id="modal_notes"
                        rows={3}
                        value={data.notes}
                        onChange={(e) => setData('notes', e.target.value)}
                        className="mt-1"
                    />
                    <InputError message={errors.notes} className="mt-1" />
                </div>

                <div className="flex justify-end gap-3">
                    <SecondaryButton type="button" onClick={handleClose}>
                        Annuler
                    </SecondaryButton>
                    <Button disabled={processing}>
                        {processing ? 'Enregistrement…' : 'Confirmer'}
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
