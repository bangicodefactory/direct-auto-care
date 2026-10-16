import type { ProductCategory, ProductUnit } from '@/types/models';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]): string {
    return twMerge(clsx(inputs));
}

/**
 * Singular/plural display forms of each stock unit, keyed by ProductUnit value.
 * Served from `ui.units` in lang/{locale}/ui.php through the shared
 * `translations` prop, the same table ProductUnit::quantityLabel() reads for
 * the PDF. Get it with useQuantityFormat() rather than passing it around.
 */
export type UnitLabels = Partial<Record<string, { one: string; other: string }>>;

/**
 * French display label for a product category value ('piece' -> 'Pièce'),
 * mirroring ProductCategory::label() on the PHP side.
 */
export const CATEGORY_LABELS: Record<ProductCategory, string> = {
    huile: 'Huile',
    filtre: 'Filtre',
    piece: 'Pièce',
    autre: 'Autre',
};

/**
 * The display form of a stock unit for a given quantity ("2 jeux", "1 jeu",
 * "5 L"). Singular when |quantity| <= 1, as on the PHP side. Unknown values —
 * legacy rows not yet normalized by the migration — are returned untouched
 * rather than guessed at.
 */
export function pluralizeUnit(
    quantity: number,
    unit: ProductUnit | string,
    labels: UnitLabels,
): string {
    const display = labels[unit];

    if (!display) {
        return unit;
    }

    return Math.abs(quantity) <= 1 ? display.one : display.other;
}

/**
 * A quantity with its unit for display ("30 L", "2 paires"), or the bare
 * number when the deployment hides units or the row has none. The one place
 * that decides how a quantity + unit pair is rendered across the app.
 */
export function formatQuantity(
    quantity: number | string,
    unit: ProductUnit | string | null | undefined,
    showUnits: boolean,
    labels: UnitLabels,
): string {
    if (!showUnits || !unit) {
        return String(quantity);
    }

    return `${quantity} ${pluralizeUnit(Number(quantity), unit, labels)}`;
}
