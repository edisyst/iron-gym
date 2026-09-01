<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AccessLogIndexRequest;
use App\Http\Resources\Api\V1\AccessLogResource;
use App\Models\AccessLog;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AccessLogController extends Controller
{
    public function index(AccessLogIndexRequest $request): AnonymousResourceCollection
    {
        $query = AccessLog::with('member')->orderByDesc('checked_in_at');

        if ($request->has('member_id')) {
            $query->where('member_id', $request->integer('member_id'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('checked_in_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('checked_in_at', '<=', $request->input('date_to'));
        }

        $perPage = (int) $request->input('per_page', 25);

        return AccessLogResource::collection($query->paginate($perPage));
    }
}
