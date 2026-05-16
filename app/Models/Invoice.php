<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'type',
        'number',
        'customer_id',
        'vehicle_id',
        'issue_date',
        'due_date',
        'fiscal_year',
        'subtotal_ht',
        'discount',
        'tva_rate',
        'tva_amount',
        'total_ttc',
        'status',
        'notes',
        'created_by',
        'converted_from_quote_id',
    ];

    protected function casts(): array
    {
        return [
            'type'       => InvoiceType::class,
            'status'     => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date'   => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function convertedFromQuote(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_from_quote_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function serviceRecords(): HasMany
    {
        return $this->hasMany(ServiceRecord::class);
    }

    public function recomputeTotals(): static
    {
        $subtotal = (float) $this->items->sum('line_total_ht');

        $this->subtotal_ht = $subtotal;
        $this->tva_amount  = round(($subtotal - (float) $this->discount) * (float) $this->tva_rate / 100, 2);
        $this->total_ttc   = round($subtotal - (float) $this->discount + $this->tva_amount, 2);

        return $this;
    }
}
