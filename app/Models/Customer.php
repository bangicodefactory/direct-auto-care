<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function setPhoneAttribute($value)
    {
        $phone = preg_replace('/\s+/', '', $value);

        if (str_starts_with($phone, '0')) {
            $phone = '+212' . substr($phone, 1);
        }

        $this->attributes['phone'] = $phone;
    }
}
