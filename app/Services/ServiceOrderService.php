<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\ServiceOrderStatus;
use App\Models\Appointment;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ServiceOrderService
{
    public function __construct(
        private readonly ServiceOrderNumberingService $numbering,
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
}
