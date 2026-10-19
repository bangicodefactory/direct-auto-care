<?php

namespace App\Models;

use App\Enums\FuelType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'make',
        'model',
        'year',
        'license_plate',
        'vin',
        'mileage',
        'fuel_type',
        'color',
    ];

    protected $casts = [
        'year'      => 'integer',
        'mileage'   => 'integer',
        'fuel_type' => FuelType::class,
    ];

    protected static function booted(): void
    {
        static::updating(function (Vehicle $vehicle) {
            if ($vehicle->isDirty('mileage') && $vehicle->mileage < $vehicle->getOriginal('mileage')) {
                throw new \DomainException('Le kilométrage ne peut pas diminuer.');
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function serviceRecords(): HasMany
    {
        return $this->hasMany(ServiceRecord::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function serviceOrders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class);
    }

    /**
     * Fleet notes logbook, newest first.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(VehicleNote::class)->latest()->latest('id');
    }

    public function latestNote(): HasOne
    {
        return $this->hasOne(VehicleNote::class)->latestOfMany();
    }
}
