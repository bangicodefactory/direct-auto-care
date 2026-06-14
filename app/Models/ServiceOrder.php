<?php

namespace App\Models;

use App\Enums\ServiceOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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
        'reception_photos',
        'fuel_level',
        'signature_path',
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
            'reception_photos'    => 'array',
            'fuel_level'          => 'integer',
            'received_at'         => 'datetime',
            'diagnosed_at'        => 'datetime',
            'quote_sent_at'       => 'datetime',
            'in_progress_at'      => 'datetime',
            'quality_control_at'  => 'datetime',
            'notified_at'         => 'datetime',
            'ready_pickup_at'     => 'datetime',
            'closed_at'           => 'datetime',
            'cancelled_at'        => 'datetime',
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

    /**
     * Public URLs for the reception photos (empty when none).
     *
     * @return array<int, string>
     */
    public function receptionPhotoUrls(): array
    {
        return collect($this->reception_photos ?? [])
            ->map(fn (string $path) => Storage::disk('public')->url($path))
            ->all();
    }

    public function signatureUrl(): ?string
    {
        return $this->signature_path ? Storage::disk('public')->url($this->signature_path) : null;
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
