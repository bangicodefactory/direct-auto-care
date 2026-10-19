<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A dated logbook entry on a fleet client's vehicle ("Notes flotte").
 */
class VehicleNote extends Model
{
    /** @use HasFactory<\Database\Factories\VehicleNoteFactory> */
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'user_id',
        'body',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * The author. Includes soft-deleted users so a departed employee's notes
     * keep their name.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
