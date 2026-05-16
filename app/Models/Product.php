<?php

namespace App\Models;

use App\Enums\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'category',
        'name',
        'sku',
        'unit',
        'price_buy',
        'price_sell',
        'quantity_on_hand',
        'low_stock_threshold',
    ];

    protected function casts(): array
    {
        return [
            'category'  => ProductCategory::class,
            'price_buy' => 'decimal:2',
            'price_sell' => 'decimal:2',
        ];
    }

    public function scopeLowStock(Builder $query): void
    {
        $query->whereColumn('quantity_on_hand', '<', 'low_stock_threshold');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
