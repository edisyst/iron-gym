<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SubscriptionPlanIndexRequest;
use App\Http\Resources\Api\V1\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubscriptionPlanController extends Controller
{
    public function index(SubscriptionPlanIndexRequest $request): AnonymousResourceCollection
    {
        $query = SubscriptionPlan::orderBy('name');

        if ($request->has('active')) {
            $active = filter_var($request->input('active'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $active);
        }

        $perPage = (int) $request->input('per_page', 25);

        return SubscriptionPlanResource::collection($query->paginate($perPage));
    }
}
