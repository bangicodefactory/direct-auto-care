export const FUEL_TYPE_OPTIONS = [
    { value: 'essence', label: 'Essence' },
    { value: 'diesel', label: 'Diesel' },
    { value: 'hybride', label: 'Hybride' },
    { value: 'electrique', label: 'Électrique' },
];

export const FUEL_LABELS = Object.fromEntries(
    FUEL_TYPE_OPTIONS.map((opt) => [opt.value, opt.label]),
);
