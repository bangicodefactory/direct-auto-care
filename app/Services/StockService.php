<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class StockService
{
    public function recordMovement(
        Product $product,
        StockMovementType $type,
        int $quantity,
        ?string $reason,
        ?Invoice $invoice,
        User $user,
    ): StockMovement {
        if ($quantity === 0) {
            throw new \InvalidArgumentException('La quantité doit être différente de zéro.');
        }

        [$movement, $oldQuantity, $newQuantity] = DB::transaction(function () use ($product, $type, $quantity, $reason, $invoice, $user) {
            $locked = Product::lockForUpdate()->findOrFail($product->id);

            $delta       = $this->delta($type, $quantity);
            $oldQuantity = $locked->quantity_on_hand;
            $newQuantity = $oldQuantity + $delta;

            if ($newQuantity < 0) {
                throw new InsufficientStockException(
                    "Stock insuffisant pour « {$locked->name} » (disponible : {$locked->quantity_on_hand}, demandé : {$quantity})."
                );
            }

            $locked->update(['quantity_on_hand' => $newQuantity]);

            return [
                StockMovement::create([
                    'product_id' => $locked->id,
                    'type'       => $type,
                    'quantity'   => $delta,
                    'reason'     => $reason,
                    'invoice_id' => $invoice?->id,
                    'user_id'    => $user->id,
                ]),
                $oldQuantity,
                $newQuantity,
            ];
        });

        if ($oldQuantity >= $product->low_stock_threshold && $newQuantity < $product->low_stock_threshold) {
            $admins = User::role('admin')->get();
            Notification::send($admins, new LowStockNotification($product));
        }

        return $movement;
    }

    private function delta(StockMovementType $type, int $quantity): int
    {
        return match ($type) {
            StockMovementType::In         =>  abs($quantity),
            StockMovementType::Out        => -abs($quantity),
            StockMovementType::Adjustment => $quantity,
        };
    }
}
