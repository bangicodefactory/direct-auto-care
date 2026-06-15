import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';

interface ServiceType {
    id: number;
    name: string;
}

interface ServiceTypeMultiCheckProps {
    serviceTypes: ServiceType[];
    selected: number[];
    onChange: (next: number[]) => void;
    error?: string;
    label?: string;
    disabled?: boolean;
}

export default function ServiceTypeMultiCheck({
    serviceTypes,
    selected,
    onChange,
    error,
    label = 'Types de service',
    disabled = false,
}: ServiceTypeMultiCheckProps) {
    function toggle(id: number) {
        if (disabled) return;
        onChange(selected.includes(id) ? selected.filter((t) => t !== id) : [...selected, id]);
    }

    return (
        <fieldset>
            <legend
                className={`text-sm font-medium ${error ? 'text-destructive' : 'text-foreground'}`}
            >
                {label}{' '}
                <span className="text-destructive" aria-hidden="true">
                    *
                </span>
            </legend>
            <div className="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                {serviceTypes.map((type) => (
                    <label
                        key={type.id}
                        className={`flex cursor-pointer items-center gap-2 text-sm text-foreground ${disabled ? 'opacity-50' : ''}`}
                    >
                        <Checkbox
                            checked={selected.includes(type.id)}
                            disabled={disabled}
                            onChange={() => toggle(type.id)}
                        />
                        {type.name}
                    </label>
                ))}
            </div>
            <InputError message={error} className="mt-2" />
        </fieldset>
    );
}
