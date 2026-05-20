import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';

export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <Button
            {...props}
            type={type}
            variant="secondary"
            disabled={disabled}
            className={cn('min-h-[44px]', className)}
        >
            {children}
        </Button>
    );
}
