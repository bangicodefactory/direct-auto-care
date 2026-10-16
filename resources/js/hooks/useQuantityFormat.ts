import { formatQuantity, pluralizeUnit, type UnitLabels } from '@/lib/utils';
import type { ProductUnit } from '@/types/models';
import { usePage } from '@inertiajs/react';

const NO_LABELS: UnitLabels = {};

/**
 * Quantity + unit formatting bound to this deployment and the user's locale:
 * `showUnits` from COMPANY_SHOW_PRODUCT_UNITS and the unit labels from
 * lang/{locale}/ui.php, so call sites only pass the quantity and the unit.
 */
export function useQuantityFormat() {
    const { appSettings, translations } = usePage().props;
    const showUnits = appSettings.show_product_units;
    const labels = translations?.ui?.units ?? NO_LABELS;

    return {
        showUnits,
        /** "2 paires", "30 L", or the bare number when units are hidden. */
        formatQuantity: (
            quantity: number | string,
            unit: ProductUnit | string | null | undefined,
        ): string => formatQuantity(quantity, unit, showUnits, labels),
        /** The unit word alone for a quantity ("paires"), regardless of `showUnits`. */
        unitLabel: (quantity: number, unit: ProductUnit | string): string =>
            pluralizeUnit(quantity, unit, labels),
    };
}
