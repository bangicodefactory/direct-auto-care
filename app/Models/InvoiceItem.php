<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'product_id',
        'service_id',
        'vehicle_id',
        'description',
        'unit',
        'quantity',
        'unit_price_ht',
        'line_total_ht',
    ];

    protected function casts(): array
    {
        return [
            'quantity'      => 'decimal:2',
            'unit_price_ht' => 'decimal:2',
            'line_total_ht' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
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

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
