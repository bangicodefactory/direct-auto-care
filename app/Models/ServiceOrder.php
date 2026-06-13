<?php

namespace App\Models;

use App\Enums\ServiceOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceOrder extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'number',
        'appointment_id',
        'customer_id',
        'vehicle_id',
        'mileage_in',
        'reception_condition',
        'status',
        'diagnosis_notes',
        'internal_notes',
        'quote_id',
        'invoice_id',
        'received_at',
        'diagnosed_at',
        'quote_sent_at',
        'in_progress_at',
        'quality_control_at',
        'notified_at',
        'ready_pickup_at',
        'closed_at',
        'cancelled_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status'              => ServiceOrderStatus::class,
            'mileage_in'          => 'integer',
            'reception_condition' => 'array',
            'received_at'        => 'datetime',
            'diagnosed_at'       => 'datetime',
            'quote_sent_at'      => 'datetime',
            'in_progress_at'     => 'datetime',
            'quality_control_at' => 'datetime',
            'notified_at'        => 'datetime',
            'ready_pickup_at'    => 'datetime',
            'closed_at'          => 'datetime',
            'cancelled_at'       => 'datetime',
        ];
    }

    /**
     * Orders that are still moving through the workshop (not Closed or Cancelled).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            ServiceOrderStatus::Closed->value,
            ServiceOrderStatus::Cancelled->value,
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'quote_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceOrderItem::class);
    }
}
