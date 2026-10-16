<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A facture line references a product that has since been removed from the
 * catalogue, so its stock cannot be decremented. Not a stock shortfall: the
 * invoice controller catches it next to InsufficientStockException and flashes
 * its (translated) message the same way.
 */
class ProductUnavailableException extends RuntimeException
{
}
