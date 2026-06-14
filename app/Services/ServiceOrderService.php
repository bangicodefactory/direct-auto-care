<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\ServiceOrderStatus;
use App\Enums\StockMovementType;
use App\Models\Appointment;
use App\Models\Product;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ServiceOrderService
{
    public function __construct(
        private readonly ServiceOrderNumberingService $numbering,
        private readonly StockService $stock,
    ) {
    }

    /**
     * Create a service order at reception.
     *
     * Expected $data keys:
     *   customer_id, vehicle_id, [appointment_id, mileage_in, internal_notes,
     *   reception_condition, fuel_level]
     *
     * Files are stored BEFORE the transaction (file I/O is not transactional);
     * if the DB write fails the stored files are deleted so no orphans remain.
     * A linked confirmed appointment is marked Completed. Returns the new order.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $photos
     */
    public function create(array $data, User $user, array $photos = [], ?UploadedFile $signature = null): ServiceOrder
    {
        $storedPaths = [];

        try {
            $photoPaths = [];
            foreach ($photos as $photo) {
                $path = $photo->store('service-orders/photos', 'public');
                $photoPaths[] = $path;
                $storedPaths[] = $path;
            }

            $signaturePath = null;
            if ($signature) {
                $signaturePath = $signature->store('service-orders/signatures', 'public');
                $storedPaths[] = $signaturePath;
            }

            return DB::transaction(function () use ($data, $user, $photoPaths, $signaturePath): ServiceOrder {
                $now = Carbon::now();

                $order = ServiceOrder::create([
                    'number'              => $this->numbering->next($now->year),
                    'appointment_id'      => $data['appointment_id'] ?? null,
                    'customer_id'         => $data['customer_id'],
                    'vehicle_id'          => $data['vehicle_id'],
                    'mileage_in'          => $data['mileage_in'] ?? null,
                    'reception_condition' => $data['reception_condition'] ?? null,
                    'reception_photos'    => $photoPaths ?: null,
                    'fuel_level'          => $data['fuel_level'] ?? null,
                    'signature_path'      => $signaturePath,
                    'internal_notes'      => $data['internal_notes'] ?? null,
                    'status'              => ServiceOrderStatus::Reception->value,
                    'received_at'         => $now,
                    'created_by'          => $user->id,
                ]);

                if (! empty($data['appointment_id'])) {
                    // Only a still-confirmed appointment is completed by check-in;
                    // never resurrect a cancelled/already-completed one.
                    Appointment::where('id', $data['appointment_id'])
                        ->where('status', AppointmentStatus::Confirmed->value)
                        ->update(['status' => AppointmentStatus::Completed->value]);
                }

                return $order;
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($storedPaths);

            throw $e;
        }
    }

    /**
     * Create a service order from a confirmed appointment (the check-in flow).
     *
     * Reuses create(), which generates the number, marks the linked appointment
     * Completed, and runs everything in one transaction. The caller must ensure the
     * appointment is Confirmed and has a vehicle (a service order requires one).
     */
    public function createFromAppointment(Appointment $appointment, User $user): ServiceOrder
    {
        return $this->create([
            'appointment_id' => $appointment->id,
            'customer_id'    => $appointment->customer_id,
            'vehicle_id'     => $appointment->vehicle_id,
        ], $user);
    }

    /**
     * Add a part / labour line to a service order.
     *
     * When a product is linked, its stock is decremented in the same transaction as
     * the item insert, so a stock shortfall (InsufficientStockException) rolls back
     * the whole add. A line with no product is free-text labour and touches no stock.
     *
     * @param  array{product_id?: int|null, description?: string|null, quantity: int|string, unit_price_ht: int|float|string}  $data
     */
    public function addItem(ServiceOrder $order, array $data, User $user): ServiceOrderItem
    {
        $quantity  = (int) $data['quantity'];
        $unitPrice = (float) $data['unit_price_ht'];

        return DB::transaction(function () use ($order, $data, $user, $quantity, $unitPrice): ServiceOrderItem {
            $product     = null;
            $description = $data['description'] ?? null;

            if (! empty($data['product_id'])) {
                $product = Product::findOrFail($data['product_id']);
                $this->stock->recordMovement(
                    product: $product,
                    type: StockMovementType::Out,
                    quantity: $quantity,
                    reason: "Pièce — ordre de service {$order->number}",
                    invoice: null,
                    user: $user,
                );
                $description ??= $product->name;
            }

            return $order->items()->create([
                'product_id'    => $product?->id,
                'description'   => $description,
                'quantity'      => $quantity,
                'unit_price_ht' => $unitPrice,
                'line_total_ht' => round($quantity * $unitPrice, 2),
            ]);
        });
    }

    /**
     * Remove a part / labour line, returning any consumed stock to inventory.
     *
     * The re-increment and the delete share one transaction so the item never
     * disappears without its stock being restored (or vice-versa).
     */
    public function removeItem(ServiceOrderItem $item, User $user): void
    {
        DB::transaction(function () use ($item, $user): void {
            if ($item->product_id) {
                $this->stock->recordMovement(
                    product: $item->product,
                    type: StockMovementType::In,
                    quantity: $item->quantity,
                    reason: "Retrait pièce — ordre de service {$item->serviceOrder->number}",
                    invoice: null,
                    user: $user,
                );
            }

            $item->delete();
        });
    }
}
