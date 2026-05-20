import { Checkbox as ShadCheckbox } from '@/Components/ui/checkbox';

export default function Checkbox({ className = '', checked, onChange, onCheckedChange, ...props }) {
    return (
        <ShadCheckbox
            checked={checked}
            onCheckedChange={onCheckedChange ?? ((c) => onChange?.({ target: { checked: c } }))}
            className={className}
            {...props}
        />
    );
}
