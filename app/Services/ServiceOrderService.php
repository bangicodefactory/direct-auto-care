<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\ServiceOrderStatus;
use App\Models\Appointment;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ServiceOrderService
{
    public function __construct(
        private readonly ServiceOrderNumberingService $numbering,
    ) {
    }

    /**
     * Create a service order at reception inside a single transaction.
     *
     * Expected $data keys:
     *   customer_id, vehicle_id, [appointment_id, mileage_in, internal_notes,
     *   reception_condition]
     *
     * A linked appointment is marked Completed. Returns the new order.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): ServiceOrder
    {
        return DB::transaction(function () use ($data, $user): ServiceOrder {
            $now = Carbon::now();

            $order = ServiceOrder::create([
                'number'              => $this->numbering->next($now->year),
                'appointment_id'      => $data['appointment_id'] ?? null,
                'customer_id'         => $data['customer_id'],
                'vehicle_id'          => $data['vehicle_id'],
                'mileage_in'          => $data['mileage_in'] ?? null,
                'reception_condition' => $data['reception_condition'] ?? null,
                'internal_notes'      => $data['internal_notes'] ?? null,
                'status'              => ServiceOrderStatus::Reception->value,
                'received_at'         => $now,
                'created_by'          => $user->id,
            ]);

            if (! empty($data['appointment_id'])) {
                Appointment::where('id', $data['appointment_id'])
                    ->update(['status' => AppointmentStatus::Completed->value]);
            }

            return $order;
        });
    }
}
