import { useMemo } from 'react';

/**
 * Index a product catalogue by id → stock unit, built once per catalogue
 * change, so line rows can resolve their unit in O(1) instead of scanning the
 * list on every render.
 */
export function useUnitByProductId(
    products: readonly { id: number | string; unit: string }[],
): (productId: number | string | null | undefined) => string | undefined {
    const map = useMemo(() => new Map(products.map((p) => [String(p.id), p.unit])), [products]);

    return (productId) => (productId ? map.get(String(productId)) : undefined);
}
