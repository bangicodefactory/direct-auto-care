import InputError from '@/Components/InputError';
import { useEffect, useRef, useState } from 'react';

export default function CustomerCombobox({ customers, value, onChange, error, inputRef }) {
    const [query, setQuery] = useState('');
    const [isOpen, setIsOpen] = useState(false);
    const containerRef = useRef(null);

    const selected = value ? (customers.find((c) => c.id === Number(value)) ?? null) : null;

    const filtered = query.trim()
        ? customers.filter(
              (c) =>
                  `${c.first_name} ${c.last_name}`.toLowerCase().includes(query.toLowerCase()) ||
                  (c.phone && c.phone.includes(query)),
          )
        : customers.slice(0, 50);

    function select(customer) {
        onChange(customer.id);
        setQuery('');
        setIsOpen(false);
    }

    function clear() {
        onChange('');
        setQuery('');
    }

    useEffect(() => {
        function handler(e) {
            if (containerRef.current && !containerRef.current.contains(e.target)) {
                setIsOpen(false);
            }
        }
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, []);

    return (
        <div ref={containerRef} className="relative">
            {selected ? (
                <div
                    className={`flex items-center gap-2 rounded-md border bg-white px-3 py-2 text-sm ${error ? 'border-red-500' : 'border-gray-300'}`}
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
                        aria-label="Effacer la sélection"
                    >
                        ✕
                    </button>
                </div>
            ) : (
                <>
                    <input
                        ref={inputRef}
                        type="text"
                        value={query}
                        onChange={(e) => {
                            setQuery(e.target.value);
                            setIsOpen(true);
                        }}
                        onFocus={() => setIsOpen(true)}
                        placeholder="Rechercher un client par nom ou téléphone…"
                        className={`block w-full rounded-md shadow-sm focus:border-primary focus:ring-primary sm:text-sm ${error ? 'border-red-500' : 'border-gray-300'}`}
                    />
                    {isOpen && (
                        <ul className="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg">
                            {filtered.length === 0 ? (
                                <li className="px-4 py-2 text-sm text-gray-500">
                                    Aucun client trouvé.
                                </li>
                            ) : (
                                filtered.map((c) => (
                                    <li key={c.id}>
                                        <button
                                            type="button"
                                            onClick={() => select(c)}
                                            className="w-full px-4 py-2 text-left text-sm hover:bg-gray-50"
                                        >
                                            <span className="font-medium">
                                                {c.first_name} {c.last_name}
                                            </span>
                                            {c.phone && (
                                                <span className="ml-2 text-xs text-gray-400">
                                                    {c.phone}
                                                </span>
                                            )}
                                        </button>
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
