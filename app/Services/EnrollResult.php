<?php

namespace App\Services;

use App\Models\ClassBooking;

readonly class EnrollResult
{
    public function __construct(
        public ?ClassBooking $booking,
        public ?EnrollFailure $failure,
    ) {}

    public function succeeded(): bool
    {
        return $this->booking !== null;
    }
}
