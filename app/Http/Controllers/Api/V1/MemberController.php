<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MemberIndexRequest;
use App\Http\Resources\Api\V1\MemberDetailResource;
use App\Http\Resources\Api\V1\MemberListResource;
use App\Models\Member;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MemberController extends Controller
{
    public function index(MemberIndexRequest $request): AnonymousResourceCollection
    {
        $query = Member::orderBy('last_name')->orderBy('first_name');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search): void {
                $q->where('last_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('cert_expiry_before') && $request->user()?->tokenCan('members:medical-read')) {
            $query->whereDate('medical_cert_expiry', '<=', $request->input('cert_expiry_before'));
        }

        $perPage = (int) $request->input('per_page', 25);

        return MemberListResource::collection($query->paginate($perPage));
    }

    public function show(Member $member): MemberDetailResource
    {
        $member->load(['activeSubscription.plan']);

        return new MemberDetailResource($member);
    }
}
