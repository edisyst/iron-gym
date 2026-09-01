<?php

namespace App\Services;

enum CancelFailure
{
    case DeadlineExceeded;
    case NotCancellable;
}
