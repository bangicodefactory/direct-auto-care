import { Checkbox as ShadCheckbox } from '@/Components/ui/checkbox';

export default function Checkbox({
    className = '',
    checked,
    onChange,
    onCheckedChange,
    ...props
}: {
    className?: string;
    checked?: boolean;
    onChange?: (e: { target: { checked: boolean } }) => void;
    onCheckedChange?: (checked: boolean) => void;
} & Omit<React.ComponentProps<typeof ShadCheckbox>, 'checked' | 'onChange' | 'onCheckedChange'>) {
    return (
        <ShadCheckbox
            checked={checked}
            onCheckedChange={
                onCheckedChange ?? ((c) => onChange?.({ target: { checked: c as boolean } }))
            }
            className={className}
            {...props}
        />
    );
}
