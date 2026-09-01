<?php

namespace App\Services;

enum CheckinFailure
{
    case MedicalCertInvalid;
    case NoActiveSubscription;
    case NoAccessesLeft;
}
