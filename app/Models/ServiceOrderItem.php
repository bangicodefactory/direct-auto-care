<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_order_id',
        'product_id',
        'service_id',
        'description',
        'unit',
        'quantity',
        'unit_price_ht',
        'line_total_ht',
    ];

    protected function casts(): array
    {
        return [
            'quantity'      => 'integer',
            'unit_price_ht' => 'decimal:2',
            'line_total_ht' => 'decimal:2',
        ];
    }

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function service(): BelongsTo
    {
        // withTrashed: historical lines must still resolve soft-deleted services.
        return $this->belongsTo(Service::class)->withTrashed();
    }
}
