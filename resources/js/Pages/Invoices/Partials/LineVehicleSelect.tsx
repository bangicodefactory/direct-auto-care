import InputError from '@/Components/InputError';
import type { Vehicle } from '@/types/models';

type VehicleOption = Pick<Vehicle, 'id' | 'license_plate' | 'make' | 'model'>;

interface LineVehicleSelectProps {
    value: string | number | null;
    vehicles: VehicleOption[];
    error?: string;
    onChange: (value: string) => void;
}

/**
 * Per-line vehicle picker rendered inside a line-item row when the client owns 2+
 * vehicles. A native <select> to match the sibling per-line product control (dense
 * table row). Shared by Invoices/Create and Invoices/Edit.
 */
export default function LineVehicleSelect({
    value,
    vehicles,
    error,
    onChange,
}: LineVehicleSelectProps) {
    return (
        <>
            <select
                aria-label="Véhicule"
                className="w-40 rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                value={value ?? ''}
                onChange={(e) => onChange(e.target.value)}
            >
                <option value="">— Aucun —</option>
                {vehicles.map((v) => (
                    <option key={v.id} value={v.id}>
                        {v.license_plate} — {v.make} {v.model}
                    </option>
                ))}
            </select>
            <InputError message={error} className="mt-0.5" />
        </>
    );
}
