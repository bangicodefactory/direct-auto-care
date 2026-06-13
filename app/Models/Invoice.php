<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'payment_method',
        'notes',
        'created_by',
        'converted_from_quote_id',
    ];

    protected function casts(): array
    {
        return [
            'type'           => InvoiceType::class,
            'status'         => InvoiceStatus::class,
            'payment_method' => PaymentMethod::class,
            'issue_date'   => 'date',
            'due_date'     => 'date',
            'subtotal_ht'  => 'decimal:2',
            'discount'     => 'decimal:2',
            'tva_rate'     => 'decimal:2',
            'tva_amount'   => 'decimal:2',
            'total_ttc'    => 'decimal:2',
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

    /**
     * The invoice that was generated from this quote, if it has already been converted.
     * A soft-deleted child is excluded, so deleting the invoice frees the quote to convert again.
     */
    public function convertedToInvoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'converted_from_quote_id');
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
        $this->loadMissing('items');

        $subtotal = (float) $this->items->sum('line_total_ht');

        $this->subtotal_ht = $subtotal;
        $this->tva_amount  = round(($subtotal - (float) $this->discount) * (float) $this->tva_rate / 100, 2);
        $this->total_ttc   = round($subtotal - (float) $this->discount + $this->tva_amount, 2);

        return $this;
    }
}
