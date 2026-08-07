import type { ProductCategory, ProductUnit } from '@/types/models';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]): string {
    return twMerge(clsx(inputs));
}

/**
 * Display forms per canonical unit, mirroring the PHP ProductUnit enum the
 * same way the ProductCategory union does. Record<ProductUnit, …> makes tsc
 * refuse to compile if the union gains a case this map forgets.
 */
const UNIT_DISPLAY: Record<ProductUnit, { singular: string; plural: string }> = {
    unite: { singular: 'unité', plural: 'unités' },
    litre: { singular: 'L', plural: 'L' },
    millilitre: { singular: 'mL', plural: 'mL' },
    kg: { singular: 'kg', plural: 'kg' },
    bidon: { singular: 'bidon', plural: 'bidons' },
    flacon: { singular: 'flacon', plural: 'flacons' },
    boite: { singular: 'boîte', plural: 'boîtes' },
    paire: { singular: 'paire', plural: 'paires' },
    jeu: { singular: 'jeu', plural: 'jeux' },
    kit: { singular: 'kit', plural: 'kits' },
};

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
 * "5 L"). Unknown values — legacy rows not yet normalized by the migration —
 * are returned untouched rather than guessed at.
 */
export function pluralizeUnit(quantity: number, unit: ProductUnit | string): string {
    const display = UNIT_DISPLAY[unit as ProductUnit];

    if (!display) {
        return unit;
    }

    return Math.abs(quantity) <= 1 ? display.singular : display.plural;
}
