import InputError from '@/Components/InputError';
import { X } from 'lucide-react';
import { useEffect, useMemo } from 'react';

/**
 * Up to `max` car photos captured at reception. Controlled: `value` is a File[],
 * `onChange` receives the next File[]. Previews use object URLs that are revoked
 * on change/unmount to avoid leaks.
 */
interface ReceptionPhotosInputProps {
    value?: File[];
    onChange: (next: File[]) => void;
    error?: string;
    max?: number;
}

export default function ReceptionPhotosInput({
    value = [],
    onChange,
    error,
    max = 3,
}: ReceptionPhotosInputProps) {
    const previews = useMemo(() => value.map((file) => window.URL.createObjectURL(file)), [value]);

    useEffect(() => () => previews.forEach((url) => window.URL.revokeObjectURL(url)), [previews]);

    function handleAdd(e: React.ChangeEvent<HTMLInputElement>) {
        const incoming = Array.from(e.target.files ?? []);
        onChange([...value, ...incoming].slice(0, max));
        e.target.value = ''; // allow re-selecting the same file after removal
    }

    function remove(index: number) {
        onChange(value.filter((_, i) => i !== index));
    }

    return (
        <div className="space-y-3">
            {value.length < max && (
                <input
                    type="file"
                    accept="image/*"
                    multiple
                    capture="environment"
                    onChange={handleAdd}
                    className="block w-full text-sm text-muted-foreground file:me-4 file:rounded-md file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-foreground hover:file:bg-primary/90"
                />
            )}

            {previews.length > 0 && (
                <div className="flex flex-wrap gap-3">
                    {previews.map((src, i) => (
                        <div key={src} className="relative">
                            <img
                                src={src}
                                alt={`Photo ${i + 1}`}
                                className="h-24 w-32 rounded-md border border-border object-cover"
                            />
                            <button
                                type="button"
                                onClick={() => remove(i)}
                                title="Retirer la photo"
                                className="absolute -end-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-destructive text-destructive-foreground shadow"
                            >
                                <X className="h-3.5 w-3.5" />
                            </button>
                        </div>
                    ))}
                </div>
            )}

            <p className="text-xs text-muted-foreground">
                {value.length}/{max} photo(s)
            </p>
            <InputError message={error} />
        </div>
    );
}
