<?php

namespace App\Services;

use App\Models\AccessLog;

readonly class CheckinResult
{
    public function __construct(
        public ?AccessLog $accessLog,
        public ?CheckinFailure $failure,
        public bool $isDuplicate = false,
    ) {}

    public function succeeded(): bool
    {
        return $this->accessLog !== null;
    }
}
