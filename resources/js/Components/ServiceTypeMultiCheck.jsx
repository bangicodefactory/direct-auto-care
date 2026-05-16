import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';

export default function ServiceTypeMultiCheck({
    serviceTypes,
    selected,
    onChange,
    error,
    label = 'Types de service',
    disabled = false,
}) {
    function toggle(id) {
        if (disabled) return;
        onChange(selected.includes(id) ? selected.filter((t) => t !== id) : [...selected, id]);
    }

    return (
        <fieldset>
            <legend className={`text-sm font-medium ${error ? 'text-red-600' : 'text-gray-700'}`}>
                {label}{' '}
                <span className="text-red-500" aria-hidden="true">
                    *
                </span>
            </legend>
            <div className="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                {serviceTypes.map((type) => (
                    <label
                        key={type.id}
                        className={`flex cursor-pointer items-center gap-2 text-sm text-gray-700 ${disabled ? 'opacity-50' : ''}`}
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
