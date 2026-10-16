import { useQuantityFormat } from '@/hooks/useQuantityFormat';

interface UnitSuffixProps {
    quantity: number | string;
    unit: string | null | undefined;
}

/**
 * Small muted unit label to sit next to a quantity input ("L", "paires").
 * Renders nothing when the deployment hides units or the line has no unit.
 */
export default function UnitSuffix({ quantity, unit }: UnitSuffixProps) {
    const { showUnits, unitLabel } = useQuantityFormat();

    if (!showUnits || !unit) {
        return null;
    }

    // An empty or half-typed input is NaN; treat it as 1 so the label stays singular.
    const parsed = Number(quantity);
    const count = Number.isFinite(parsed) ? parsed : 1;

    return (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
            {unitLabel(count, unit)}
        </span>
    );
}
