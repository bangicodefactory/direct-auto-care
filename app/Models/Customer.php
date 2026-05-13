<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'postal_code',
        'license_plate',
        'vehicle_make',
        'vehicle_model',
        'vehicle_year',
    ];

    protected $casts = [
        'vehicle_year' => 'integer',
    ];
}
