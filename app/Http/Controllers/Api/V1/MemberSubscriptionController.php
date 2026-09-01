<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SubscriptionResource;
use App\Models\Member;
use Illuminate\Http\JsonResponse;

class MemberSubscriptionController extends Controller
{
    public function show(Member $member): SubscriptionResource|JsonResponse
    {
        $subscription = $member->activeSubscription()->with('plan')->first();

        if (! $subscription) {
            return response()->json(['message' => 'Abbonamento non trovato.', 'code' => 'not_found'], 404);
        }

        return new SubscriptionResource($subscription);
    }
}
