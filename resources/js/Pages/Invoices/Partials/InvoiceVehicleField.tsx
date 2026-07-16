import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import type { Vehicle } from '@/types/models';

type VehicleOption = Pick<Vehicle, 'id' | 'license_plate' | 'make' | 'model'>;

interface InvoiceVehicleFieldProps {
    /** True when the selected client owns 2+ vehicles — attribution then moves per line. */
    isMultiVehicle: boolean;
    value: string | number | null;
    vehicles: VehicleOption[];
    disabled?: boolean;
    error?: string;
    /** Receives the resolved id as a string, or '' for "— Aucun —". */
    onChange: (value: string) => void;
}

/**
 * Header-level vehicle selector on the invoice/quote form. For a multi-vehicle client
 * it collapses to a hint pointing at the per-line Véhicule column; otherwise it renders
 * the single-vehicle picker. Shared by Invoices/Create and Invoices/Edit.
 */
export default function InvoiceVehicleField({
    isMultiVehicle,
    value,
    vehicles,
    disabled,
    error,
    onChange,
}: InvoiceVehicleFieldProps) {
    if (isMultiVehicle) {
        return (
            <div>
                <InputLabel value="Véhicule" />
                <p className="mt-2 text-sm text-muted-foreground">
                    Ce client a plusieurs véhicules — précisez le véhicule sur chaque ligne
                    ci-dessous.
                </p>
            </div>
        );
    }

    return (
        <div>
            <InputLabel htmlFor="vehicle_id" value="Véhicule" />
            <Select
                value={value ? String(value) : '__none__'}
                onValueChange={(v) => onChange(v === '__none__' ? '' : v)}
                disabled={disabled}
            >
                <SelectTrigger id="vehicle_id" className="mt-1 min-h-[44px] w-full">
                    <SelectValue placeholder="— Aucun —" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="__none__">— Aucun —</SelectItem>
                    {vehicles.map((v) => (
                        <SelectItem key={v.id} value={String(v.id)}>
                            {v.license_plate} — {v.make} {v.model}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <InputError message={error} className="mt-1" />
        </div>
    );
}
