<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MileageNotDecreasing implements ValidationRule
{
    public function __construct(private readonly int $currentMileage)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ((int) $value < $this->currentMileage) {
            $fail(trans('validation.mileage_not_decreasing'));
        }
    }
}
