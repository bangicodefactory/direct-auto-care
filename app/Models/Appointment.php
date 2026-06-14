<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $appends = ['ends_at'];

    protected $fillable = [
        'customer_id',
        'vehicle_id',
        'created_by',
        'scheduled_at',
        'duration',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'duration'     => 'integer',
            'status'       => AppointmentStatus::class,
        ];
    }

    /**
     * When the appointment ends: scheduled_at + duration minutes.
     * Appended to all JSON/array representations for calendar rendering.
     */
    protected function endsAt(): Attribute
    {
        return Attribute::get(
            fn () => $this->scheduled_at?->copy()->addMinutes($this->duration)
        );
    }

    /**
     * Service type names as a plain array.
     * Proxies the serviceTypes BelongsToMany; caller must eager-load the relation.
     *
     * @return string[]
     */
    protected function expectedServices(): Attribute
    {
        return Attribute::get(
            fn () => $this->serviceTypes->pluck('name')->all()
        );
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function serviceTypes(): BelongsToMany
    {
        return $this->belongsToMany(ServiceType::class);
    }

    /**
     * The service order created when this appointment is checked in (at most one).
     */
    public function serviceOrder(): HasOne
    {
        return $this->hasOne(ServiceOrder::class);
    }
}
