import { usePage } from '@inertiajs/react';

/**
 * Whether this deployment shows stock units next to product quantities
 * (COMPANY_SHOW_PRODUCT_UNITS, shared as `appSettings.show_product_units`).
 * When false, render the bare number and hide the unit field on products.
 */
export function useShowUnits(): boolean {
    return usePage().props.appSettings.show_product_units;
}
