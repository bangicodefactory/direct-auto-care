import InputError from '@/Components/InputError';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function CustomerCombobox({ customers, value, onChange, error, inputRef }) {
    const { t } = useTranslation();

    const [query, setQuery] = useState('');
    const [isOpen, setIsOpen] = useState(false);
    const [activeIndex, setActiveIndex] = useState(-1);
    const containerRef = useRef(null);
    const listRef = useRef(null);

    const selected = value ? (customers.find((c) => c.id === Number(value)) ?? null) : null;

    const filtered = query.trim()
        ? customers.filter(
              (c) =>
                  `${c.first_name} ${c.last_name}`.toLowerCase().includes(query.toLowerCase()) ||
                  (c.phone && c.phone.includes(query)),
          )
        : customers;

    useEffect(() => {
        if (activeIndex >= 0 && listRef.current) {
            const items = listRef.current.querySelectorAll('[role="option"]');
            items[activeIndex]?.scrollIntoView({ block: 'nearest' });
        }
    }, [activeIndex]);

    function select(customer) {
        onChange(customer.id);
        setQuery('');
        setIsOpen(false);
        setActiveIndex(-1);
    }

    function clear() {
        onChange('');
        setQuery('');
    }

    function handleKeyDown(e) {
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
        function handler(e) {
            if (containerRef.current && !containerRef.current.contains(e.target)) {
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
                    className={`flex items-center gap-2 rounded-md border bg-white px-3 py-2 text-sm ${
                        error ? 'border-red-500' : 'border-gray-300'
                    }`}
                >
                    <span className="flex-1 font-medium">
                        {selected.first_name} {selected.last_name}
                        {selected.phone && (
                            <span className="ml-2 font-normal text-gray-400">{selected.phone}</span>
                        )}
                    </span>

                    <button
                        type="button"
                        onClick={clear}
                        className="text-gray-400 hover:text-gray-600"
                        aria-label={t('customer.clear_selection')}
                    >
                        ✕
                    </button>
                </div>
            ) : (
                <>
                    <input
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
                        placeholder={t('customer.search_placeholder')}
                        className={`block w-full rounded-md shadow-sm focus:border-primary focus:ring-primary sm:text-sm ${
                            error ? 'border-red-500' : 'border-gray-300'
                        }`}
                    />

                    {isOpen && (
                        <ul
                            ref={listRef}
                            role="listbox"
                            id="customer-listbox"
                            aria-label={t('customer.list_label')}
                            className="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg"
                        >
                            {filtered.length === 0 ? (
                                <li role="presentation" className="px-4 py-2 text-sm text-gray-500">
                                    {t('customer.no_results')}
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
                                                : 'hover:bg-gray-50'
                                        }`}
                                    >
                                        <span className="font-medium">
                                            {c.first_name} {c.last_name}
                                        </span>

                                        {c.phone && (
                                            <span className="ml-2 text-xs text-gray-400">
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
