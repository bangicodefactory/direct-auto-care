import { useEffect, useMemo, useRef, useState } from 'react';

const SVG_URL = '/diagrams/vehicle-top.svg';

// Damage legend — each type marks a body part with a colour.
const DAMAGE_TYPES = [
    { color: '#FF1A1A', label: 'Rayure' },
    { color: '#E65100', label: 'Bosse' },
    { color: '#FFD700', label: 'Éclat' },
    { color: '#111111', label: 'Cassé / manquant' },
];

function formatPart(id: string) {
    return id
        .toLowerCase()
        .replace(/_/g, ' ')
        .replace(/^\w/, (c) => c.toUpperCase());
}

/**
 * Interactive top-down vehicle body diagram used at reception to record the
 * vehicle's condition. Click a body part to mark it with the selected damage
 * type; click again with "Effacer" to clear it.
 *
 * `value` is a controlled { PART_ID: "#hexcolor" } map. `onChange` receives the
 * updated map. When `readOnly`, the diagram only renders the marks.
 */
type ConditionMarks = Record<string, string>;

interface VehicleConditionDiagramProps {
    value?: ConditionMarks | null;
    onChange?: (next: ConditionMarks) => void;
    readOnly?: boolean;
}

export default function VehicleConditionDiagram({
    value,
    onChange,
    readOnly = false,
}: VehicleConditionDiagramProps) {
    const marksMap = useMemo<ConditionMarks>(() => value ?? {}, [value]);
    const containerRef = useRef<HTMLDivElement>(null);
    const [loaded, setLoaded] = useState(false);
    const [selected, setSelected] = useState(DAMAGE_TYPES[0].color);

    // Keep the latest value/selection/handler in refs so the delegated click
    // listener (bound once) doesn't need re-attaching on every mark.
    const valueRef = useRef<ConditionMarks>(marksMap);
    const selectedRef = useRef<string>(selected);
    const onChangeRef = useRef<VehicleConditionDiagramProps['onChange']>(onChange);
    useEffect(() => {
        valueRef.current = marksMap;
        selectedRef.current = selected;
        onChangeRef.current = onChange;
    });

    // Inject the SVG once.
    useEffect(() => {
        let cancelled = false;
        window
            .fetch(SVG_URL)
            .then((r) => r.text())
            .then((svg) => {
                if (cancelled || !containerRef.current) return;
                containerRef.current.innerHTML = svg;
                const el = containerRef.current.querySelector<SVGSVGElement>('svg');
                if (el) {
                    el.style.width = '100%';
                    el.style.height = 'auto';
                    el.removeAttribute('height');
                }
                setLoaded(true);
            });
        return () => {
            cancelled = true;
        };
    }, []);

    // Paint the current value onto the SVG whenever it (or the SVG) changes.
    useEffect(() => {
        const root = containerRef.current;
        if (!loaded || !root) return;

        root.querySelectorAll<SVGGElement>('g[id]').forEach((group) => {
            const color = marksMap[group.id];
            group
                .querySelectorAll<SVGElement>('path, polygon, rect, circle, ellipse')
                .forEach((shape) => {
                    shape.style.fill = color ?? '';
                });
        });
    }, [marksMap, loaded]);

    // Wire click handling (delegated) once loaded — reads live state via refs.
    useEffect(() => {
        const root = containerRef.current;
        if (!loaded || !root || readOnly) return;

        function handleClick(e: MouseEvent) {
            e.preventDefault(); // SVG parts are wrapped in <a> — stop navigation.
            const group = (e.target as Element).closest('g[id]');
            if (!group || !root!.contains(group)) return;

            const next = { ...valueRef.current };
            if (selectedRef.current === 'clear') {
                delete next[group.id];
            } else {
                next[group.id] = selectedRef.current;
            }
            onChangeRef.current?.(next);
        }

        root.addEventListener('click', handleClick);
        return () => root.removeEventListener('click', handleClick);
    }, [loaded, readOnly]);

    const marks = Object.entries(marksMap);

    return (
        <div className="space-y-3">
            {!readOnly && (
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-sm text-muted-foreground">Type :</span>
                    {DAMAGE_TYPES.map((d) => (
                        <button
                            key={d.color}
                            type="button"
                            onClick={() => setSelected(d.color)}
                            className={`inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1 text-xs font-medium transition ${
                                selected === d.color
                                    ? 'border-primary ring-2 ring-primary/40'
                                    : 'border-border'
                            }`}
                        >
                            <span
                                className="h-3 w-3 rounded-full border border-black/10"
                                style={{ background: d.color }}
                            />
                            {d.label}
                        </button>
                    ))}
                    <button
                        type="button"
                        onClick={() => setSelected('clear')}
                        className={`rounded-md border px-2.5 py-1 text-xs font-medium transition ${
                            selected === 'clear'
                                ? 'border-primary ring-2 ring-primary/40'
                                : 'border-border'
                        }`}
                    >
                        Effacer
                    </button>
                </div>
            )}

            {!loaded && (
                <div className="flex h-48 animate-pulse items-center justify-center rounded-lg border border-border bg-white text-sm text-muted-foreground">
                    Chargement du schéma…
                </div>
            )}

            {/* React never renders children here — the SVG is injected via innerHTML,
                so this node must stay childless to avoid reconciliation conflicts. */}
            <div
                ref={containerRef}
                className="rounded-lg border border-border bg-white p-2"
                aria-label="Schéma de l'état du véhicule"
                style={{ display: loaded ? 'block' : 'none' }}
            />

            {marks.length > 0 && (
                <ul className="flex flex-wrap gap-2 text-xs">
                    {marks.map(([part, color]) => (
                        <li
                            key={part}
                            className="inline-flex items-center gap-1.5 rounded-full border border-border px-2 py-0.5"
                        >
                            <span
                                className="h-2.5 w-2.5 rounded-full border border-black/10"
                                style={{ background: color }}
                            />
                            {formatPart(part)}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
