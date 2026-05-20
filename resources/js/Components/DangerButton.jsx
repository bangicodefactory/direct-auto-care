import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';

export default function DangerButton({ className = '', disabled, children, ...props }) {
    return (
        <Button
            {...props}
            variant="destructive"
            disabled={disabled}
            className={cn('min-h-[44px]', className)}
        >
            {children}
        </Button>
    );
}
