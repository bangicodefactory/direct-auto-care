import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]): string {
    return twMerge(clsx(inputs));
}

/**
 * Pluralizes a French stock unit label for display (e.g. "unité" -> "unités").
 * Short abbreviations (L, kg, mL...) are invariant in French and left untouched.
 */
export function pluralizeUnit(quantity: number, unit: string): string {
    if (Math.abs(quantity) <= 1 || unit.length <= 2 || unit.endsWith('s')) {
        return unit;
    }
    return `${unit}s`;
}
