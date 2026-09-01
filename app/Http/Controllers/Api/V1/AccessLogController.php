<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AccessLogIndexRequest;
use App\Http\Requests\Api\V1\AccessLogStoreRequest;
use App\Http\Resources\Api\V1\AccessLogResource;
use App\Models\AccessLog;
use App\Models\Member;
use App\Models\User;
use App\Services\AccessService;
use App\Services\CheckinFailure;
use Illuminate\Http\JsonResponse;
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

    public function store(AccessLogStoreRequest $request): JsonResponse
    {
        $member = Member::find($request->integer('member_id'));

        if ($member === null) {
            return response()->json([
                'message' => 'Tesserato non trovato.',
                'code' => 'not_found',
            ], 404);
        }

        /** @var User $user */
        $user = $request->user();

        $result = app(AccessService::class)->checkin(
            $member,
            $user->id,
            idempotencyWindowMinutes: config('api.checkin_idempotency_window_minutes'),
            note: $request->string('note')->toString() ?: null,
        );

        if ($result->succeeded()) {
            $log = $result->accessLog;
            assert($log !== null);
            $log->load('member');

            $status = $result->isDuplicate ? 200 : 201;
            $httpResponse = (new AccessLogResource($log))
                ->response()
                ->setStatusCode($status);

            if (! $result->isDuplicate) {
                $httpResponse->header('Location', '/api/v1/access-logs/'.$log->id);
            }

            return $httpResponse;
        }

        [$message, $code] = match ($result->failure) {
            CheckinFailure::MedicalCertInvalid => ['Certificato medico scaduto o mancante.', 'cert_invalid'],
            CheckinFailure::NoActiveSubscription => ['Nessun abbonamento attivo.', 'subscription_inactive'],
            CheckinFailure::NoAccessesLeft => ['Accessi esauriti.', 'accesses_exhausted'],
            null => throw new \LogicException('CheckinResult senza failure né successo.'),
        };

        return response()->json(['message' => $message, 'code' => $code], 422);
    }
}
