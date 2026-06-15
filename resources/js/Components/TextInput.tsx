import { Input } from '@/Components/ui/input';
import { cn } from '@/lib/utils';
import { forwardRef, useEffect, useImperativeHandle, useRef } from 'react';

export default forwardRef<
    HTMLInputElement,
    React.InputHTMLAttributes<HTMLInputElement> & { isFocused?: boolean }
>(function TextInput({ type = 'text', className = '', isFocused = false, ...props }, ref) {
    const localRef = useRef<HTMLInputElement>(null);

    useImperativeHandle(
        ref,
        () =>
            ({
                focus: () => localRef.current?.focus(),
            }) as HTMLInputElement,
    );

    useEffect(() => {
        if (isFocused) {
            localRef.current?.focus();
        }
    }, [isFocused]);

    return (
        <Input {...props} type={type} className={cn('min-h-[44px]', className)} ref={localRef} />
    );
});
