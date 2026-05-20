import { Button } from '@/Components/ui/button';
import { cn } from '@/lib/utils';

export default function PrimaryButton({ className = '', disabled, children, ...props }) {
    return (
        <Button {...props} disabled={disabled} className={cn('min-h-[44px]', className)}>
            {children}
        </Button>
    );
}
