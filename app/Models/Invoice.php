<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = ['customer_id'];

    public function serviceRecords(): HasMany
    {
        return $this->hasMany(ServiceRecord::class);
    }
}
