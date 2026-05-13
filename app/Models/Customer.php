<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function setPhoneAttribute(string $value): void
    {
        $phone = preg_replace('/[\s\-().]+/', '', $value);

        if (str_starts_with($phone, '+212')) {
            $this->attributes['phone'] = $phone;
        } elseif (str_starts_with($phone, '00212')) {
            $this->attributes['phone'] = '+' . substr($phone, 2);
        } elseif (str_starts_with($phone, '0')) {
            $this->attributes['phone'] = '+212' . substr($phone, 1);
        } else {
            // Bare national digits (e.g. "612345678"). The Form Request for
            // customer create/update must validate the format before this runs.
            $this->attributes['phone'] = '+212' . $phone;
        }
    }
}
