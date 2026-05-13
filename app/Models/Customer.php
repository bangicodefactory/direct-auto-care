<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'address',
        'notes',
        'notify_email',
        'notify_whatsapp',
    ];

    protected $casts = [
        'notify_email' => 'boolean',
        'notify_whatsapp' => 'boolean',
    ];
}
