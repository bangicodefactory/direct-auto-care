<?php

namespace App\Exceptions;

/**
 * A facture line references a product that has since been removed from the
 * catalogue, so its stock cannot be decremented. Extends the stock exception
 * so every caller that already reports stock problems to the user reports
 * this one the same way.
 */
class ProductUnavailableException extends InsufficientStockException
{
}
