import { Dialog, DialogContent, DialogTitle } from '@/Components/ui/dialog';
import { cn } from '@/lib/utils';

const maxWidthClasses = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
};

export default function Modal({
    children,
    show = false,
    maxWidth = '2xl',
    closeable = true,
    onClose = () => {},
    title = '',
}) {
    return (
        <Dialog
            open={show}
            onOpenChange={(open) => {
                if (!open && closeable) onClose();
            }}
        >
            <DialogContent
                className={cn(
                    maxWidthClasses[maxWidth],
                    !closeable && '[&>button:last-of-type]:hidden',
                )}
                onInteractOutside={(e) => {
                    if (!closeable) e.preventDefault();
                }}
                onEscapeKeyDown={(e) => {
                    if (!closeable) e.preventDefault();
                }}
            >
                <DialogTitle className={title ? undefined : 'sr-only'}>
                    {title || 'Dialogue'}
                </DialogTitle>
                {children}
            </DialogContent>
        </Dialog>
    );
}
