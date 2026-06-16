import CustomerCombobox from '@/Components/CustomerCombobox';
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
import { FUEL_TYPE_OPTIONS } from '@/constants/fuelTypes';
import { Link } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

const FIELD_ORDER = [
    'customer_id',
    'make',
    'model',
    'year',
    'license_plate',
    'vin',
    'mileage',
    'fuel_type',
    'color',
];

interface VehicleFormData {
    customer_id: string | number;
    make: string;
    model: string;
    year: string | number;
    license_plate: string;
    vin: string;
    mileage: string | number;
    fuel_type: string;
    color: string;
}

interface VehicleFormCustomer {
    id: number;
    first_name: string;
    last_name: string;
    phone?: string | null;
}

interface VehicleFormProps {
    data: VehicleFormData;
    setData: (key: string, value: string | number) => void;
    errors: Record<string, string>;
    processing: boolean;
    onSubmit: (e: React.FormEvent) => void;
    cancelHref: string;
    autoFocus?: boolean;
    customers?: VehicleFormCustomer[] | null;
    minMileage?: number | null;
}

export default function VehicleForm({
    data,
    setData,
    errors,
    processing,
    onSubmit,
    cancelHref,
    autoFocus = true,
    customers = null,
    minMileage = null,
}: VehicleFormProps) {
    const inputRefs = useRef<Record<string, HTMLElement | null>>({});

    const mileageDecreasing =
        minMileage !== null && data.mileage !== '' && Number(data.mileage) < minMileage;

    useEffect(() => {
        const firstErrorField = FIELD_ORDER.find((field) => errors[field]);
        if (firstErrorField) {
            inputRefs.current[firstErrorField]?.focus();
        }
    }, [errors]);

    return (
        <form onSubmit={onSubmit} className="space-y-6 p-6">
            {customers !== null && (
                <div>
                    <InputLabel value="Client" />
                    <CustomerCombobox
                        customers={customers}
                        value={data.customer_id}
                        onChange={(id) => setData('customer_id', id)}
                        error={errors.customer_id}
                        inputRef={(el) => {
                            inputRefs.current.customer_id = el;
                        }}
                    />
                </div>
            )}

            <div className="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel htmlFor="make" value="Marque" />
                    <TextInput
                        id="make"
                        ref={(el) => {
                            inputRefs.current.make = el;
                        }}
                        value={data.make}
                        className="mt-1 block w-full"
                        isFocused={autoFocus && customers === null}
                        onChange={(e) => setData('make', e.target.value)}
                    />
                    <InputError message={errors.make} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="model" value="Modèle" />
                    <TextInput
                        id="model"
                        ref={(el) => {
                            inputRefs.current.model = el;
                        }}
                        value={data.model}
                        className="mt-1 block w-full"
                        onChange={(e) => setData('model', e.target.value)}
                    />
                    <InputError message={errors.model} className="mt-2" />
                </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel htmlFor="year" value="Année" />
                    <TextInput
                        id="year"
                        type="number"
                        ref={(el) => {
                            inputRefs.current.year = el;
                        }}
                        value={data.year}
                        className="mt-1 block w-full"
                        min={1950}
                        max={new Date().getFullYear() + 1}
                        onChange={(e) => setData('year', e.target.value)}
                    />
                    <InputError message={errors.year} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="fuel_type" value="Carburant" />
                    <Select
                        value={data.fuel_type || ''}
                        onValueChange={(v) => setData('fuel_type', v)}
                    >
                        <SelectTrigger
                            id="fuel_type"
                            ref={(el) => {
                                inputRefs.current.fuel_type = el;
                            }}
                            className="mt-1 min-h-[44px]"
                        >
                            <SelectValue placeholder="— Sélectionner —" />
                        </SelectTrigger>
                        <SelectContent>
                            {FUEL_TYPE_OPTIONS.map((opt) => (
                                <SelectItem key={opt.value} value={opt.value}>
                                    {opt.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.fuel_type} className="mt-2" />
                </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel htmlFor="license_plate" value="Plaque d'immatriculation" />
                    <TextInput
                        id="license_plate"
                        ref={(el) => {
                            inputRefs.current.license_plate = el;
                        }}
                        value={data.license_plate}
                        className="mt-1 block w-full"
                        placeholder="12345-A-01"
                        onChange={(e) => setData('license_plate', e.target.value)}
                    />
                    <InputError message={errors.license_plate} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="color" value="Couleur" />
                    <TextInput
                        id="color"
                        ref={(el) => {
                            inputRefs.current.color = el;
                        }}
                        value={data.color}
                        className="mt-1 block w-full"
                        onChange={(e) => setData('color', e.target.value)}
                    />
                    <InputError message={errors.color} className="mt-2" />
                </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div>
                    <InputLabel htmlFor="mileage" value="Kilométrage (km)" />
                    <TextInput
                        id="mileage"
                        type="number"
                        ref={(el) => {
                            inputRefs.current.mileage = el;
                        }}
                        value={data.mileage}
                        className="mt-1 block w-full"
                        min={minMileage ?? 0}
                        onChange={(e) => setData('mileage', e.target.value)}
                    />
                    {mileageDecreasing && (
                        <p className="mt-2 text-sm text-amber-600">
                            Le kilométrage ne peut pas être inférieur à{' '}
                            {minMileage.toLocaleString('fr-MA')} km.
                        </p>
                    )}
                    <InputError message={errors.mileage} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="vin" value="Numéro de châssis (VIN)" />
                    <TextInput
                        id="vin"
                        ref={(el) => {
                            inputRefs.current.vin = el;
                        }}
                        value={data.vin}
                        className="mt-1 block w-full"
                        placeholder="17 caractères"
                        onChange={(e) => setData('vin', e.target.value)}
                    />
                    <InputError message={errors.vin} className="mt-2" />
                </div>
            </div>

            <div className="flex items-center justify-end gap-4">
                <Button variant="ghost" asChild>
                    <Link href={cancelHref}>Annuler</Link>
                </Button>
                <Button disabled={processing || mileageDecreasing}>Enregistrer</Button>
            </div>
        </form>
    );
}
