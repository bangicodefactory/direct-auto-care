<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    public function __construct(private readonly Product $product)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'low_stock',
            'message' => "Stock faible\u{00A0}: {$this->product->name} ({$this->product->quantity_on_hand} restant(s))",
            'url'     => route('products.index'),
        ];
    }
}
