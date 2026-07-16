import InputError from '@/Components/InputError';
import { Input } from '@/Components/ui/input';
import { cn } from '@/lib/utils';
import { useEffect, useRef, useState } from 'react';

interface CustomerComboboxCustomer {
    id: number;
    display_name?: string | null;
    first_name?: string | null;
    last_name?: string | null;
    phone?: string | null;
}

function customerLabel(c: CustomerComboboxCustomer): string {
    return c.display_name || `${c.first_name ?? ''} ${c.last_name ?? ''}`.trim();
}

interface CustomerComboboxProps {
    customers: CustomerComboboxCustomer[];
    value: string | number;
    onChange: (id: number | string) => void;
    error?: string;
    inputRef?: React.Ref<HTMLInputElement>;
}

export default function CustomerCombobox({
    customers,
    value,
    onChange,
    error,
    inputRef,
}: CustomerComboboxProps) {
    const [query, setQuery] = useState('');
    const [isOpen, setIsOpen] = useState(false);
    const [activeIndex, setActiveIndex] = useState(-1);
    const containerRef = useRef<HTMLDivElement>(null);
    const listRef = useRef<HTMLUListElement>(null);

    const selected = value ? (customers.find((c) => c.id === Number(value)) ?? null) : null;

    const filtered = query.trim()
        ? customers.filter(
              (c) =>
                  customerLabel(c).toLowerCase().includes(query.toLowerCase()) ||
                  (c.phone && c.phone.includes(query)),
          )
        : customers;

    useEffect(() => {
        if (activeIndex >= 0 && listRef.current) {
            const items = listRef.current.querySelectorAll('[role="option"]');
            items[activeIndex]?.scrollIntoView({ block: 'nearest' });
        }
    }, [activeIndex]);

    function select(customer: CustomerComboboxCustomer) {
        onChange(customer.id);
        setQuery('');
        setIsOpen(false);
        setActiveIndex(-1);
    }

    function clear() {
        onChange('');
        setQuery('');
    }

    function handleKeyDown(e: React.KeyboardEvent<HTMLInputElement>) {
        if (!isOpen) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                setIsOpen(true);
                setActiveIndex(0);
            }
            return;
        }
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                setActiveIndex((i) => Math.min(i + 1, filtered.length - 1));
                break;
            case 'ArrowUp':
                e.preventDefault();
                setActiveIndex((i) => Math.max(i - 1, 0));
                break;
            case 'Enter':
                e.preventDefault();
                if (activeIndex >= 0 && filtered[activeIndex]) {
                    select(filtered[activeIndex]);
                }
                break;
            case 'Escape':
                e.preventDefault();
                setIsOpen(false);
                setActiveIndex(-1);
                break;
            case 'Tab':
                setIsOpen(false);
                setActiveIndex(-1);
                break;
        }
    }

    useEffect(() => {
        function handler(e: MouseEvent) {
            if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
                setIsOpen(false);
                setActiveIndex(-1);
            }
        }
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, []);

    return (
        <div ref={containerRef} className="relative">
            {selected ? (
                <div
                    className={cn(
                        'flex items-center gap-2 rounded-md border bg-background px-3 py-2 text-sm',
                        error ? 'border-destructive' : 'border-input',
                    )}
                >
                    <span className="flex-1 font-medium">
                        {customerLabel(selected)}
                        {selected.phone && (
                            <span className="ms-2 font-normal text-muted-foreground">
                                {selected.phone}
                            </span>
                        )}
                    </span>
                    <button
                        type="button"
                        onClick={clear}
                        className="text-muted-foreground hover:text-foreground"
                        aria-label="Effacer la sélection"
                    >
                        ✕
                    </button>
                </div>
            ) : (
                <>
                    <Input
                        ref={inputRef}
                        id="customer-combobox"
                        role="combobox"
                        aria-expanded={isOpen}
                        aria-haspopup="listbox"
                        aria-autocomplete="list"
                        aria-controls="customer-listbox"
                        aria-activedescendant={
                            activeIndex >= 0 && filtered[activeIndex]
                                ? `customer-option-${filtered[activeIndex].id}`
                                : undefined
                        }
                        type="text"
                        value={query}
                        onChange={(e) => {
                            setQuery(e.target.value);
                            setIsOpen(true);
                            setActiveIndex(-1);
                        }}
                        onFocus={() => setIsOpen(true)}
                        onKeyDown={handleKeyDown}
                        placeholder="Rechercher un client par nom ou téléphone…"
                        className={cn('min-h-[44px]', error && 'border-destructive')}
                    />
                    {isOpen && (
                        <ul
                            ref={listRef}
                            role="listbox"
                            id="customer-listbox"
                            aria-label="Clients"
                            className="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border border-border bg-popover py-1 shadow-lg"
                        >
                            {filtered.length === 0 ? (
                                <li
                                    role="presentation"
                                    className="px-4 py-2 text-sm text-muted-foreground"
                                >
                                    Aucun client trouvé.
                                </li>
                            ) : (
                                filtered.map((c, i) => (
                                    <li
                                        key={c.id}
                                        id={`customer-option-${c.id}`}
                                        role="option"
                                        aria-selected={i === activeIndex}
                                        onClick={() => select(c)}
                                        onMouseEnter={() => setActiveIndex(i)}
                                        className={`cursor-pointer px-4 py-2 text-sm ${
                                            i === activeIndex
                                                ? 'bg-primary/10 text-primary'
                                                : 'hover:bg-accent hover:text-accent-foreground'
                                        }`}
                                    >
                                        <span className="font-medium">{customerLabel(c)}</span>
                                        {c.phone && (
                                            <span className="ms-2 text-xs text-muted-foreground">
                                                {c.phone}
                                            </span>
                                        )}
                                    </li>
                                ))
                            )}
                        </ul>
                    )}
                </>
            )}
            <InputError message={error} className="mt-2" />
        </div>
    );
}
