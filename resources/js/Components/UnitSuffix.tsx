import { useShowUnits } from '@/hooks/useShowUnits';
import { pluralizeUnit } from '@/lib/utils';

interface UnitSuffixProps {
    quantity: number | string;
    unit: string | null | undefined;
}

/**
 * Small muted unit label to sit next to a quantity input ("L", "paires").
 * Renders nothing when the deployment hides units or the line has no unit.
 */
export default function UnitSuffix({ quantity, unit }: UnitSuffixProps) {
    const showUnits = useShowUnits();

    if (!showUnits || !unit) {
        return null;
    }

    return (
        <span className="whitespace-nowrap text-xs text-muted-foreground">
            {pluralizeUnit(Number(quantity), unit)}
        </span>
    );
}
