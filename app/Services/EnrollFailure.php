<?php

namespace App\Services;

enum EnrollFailure
{
    case NotOpenYet;
    case BookingClosed;
    case OccurrenceNotEnrollable;
    case NoSubscription;
    case NoCert;
    case AlreadyEnrolled;
    case AthleteOverlap;
    case PtOverlap;
}
