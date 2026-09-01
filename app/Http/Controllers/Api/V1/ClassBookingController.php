<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ClassBookingIndexRequest;
use App\Http\Requests\Api\V1\ClassBookingStoreRequest;
use App\Http\Resources\Api\V1\ClassBookingResource;
use App\Models\ClassBooking;
use App\Models\ClassOccurrence;
use App\Models\Member;
use App\Models\Setting;
use App\Services\CancelFailure;
use App\Services\ClassBookingService;
use App\Services\EnrollFailure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClassBookingController extends Controller
{
    private function moduleDisabledResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Il modulo corsi collettivi non è attivo.',
            'code' => 'module_disabled',
        ], 503);
    }

    public function index(ClassBookingIndexRequest $request): AnonymousResourceCollection|JsonResponse
    {
        if (! Setting::bool('group_classes_enabled', false)) {
            return $this->moduleDisabledResponse();
        }

        $query = ClassBooking::with('member')->orderByDesc('created_at');

        if ($request->has('member_id')) {
            $query->where('member_id', $request->integer('member_id'));
        }

        if ($request->has('occurrence_id')) {
            $query->where('class_occurrence_id', $request->integer('occurrence_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = (int) $request->input('per_page', 25);

        return ClassBookingResource::collection($query->paginate($perPage));
    }

    public function store(ClassBookingStoreRequest $request): JsonResponse
    {
        if (! Setting::bool('group_classes_enabled', false)) {
            return $this->moduleDisabledResponse();
        }

        $member = Member::find($request->integer('member_id'));

        if ($member === null) {
            return response()->json(['message' => 'Tesserato non trovato.', 'code' => 'not_found'], 404);
        }

        $occurrence = ClassOccurrence::find($request->integer('class_occurrence_id'));

        if ($occurrence === null) {
            return response()->json(['message' => 'Occorrenza non trovata.', 'code' => 'not_found'], 404);
        }

        $result = app(ClassBookingService::class)->enroll($occurrence, $member);

        if ($result->succeeded()) {
            $booking = $result->booking;
            assert($booking !== null);
            $booking->load('member');

            return (new ClassBookingResource($booking))
                ->response()
                ->setStatusCode(201)
                ->header('Location', '/api/v1/class-bookings/'.$booking->id);
        }

        if ($result->failure === EnrollFailure::AlreadyEnrolled) {
            $existing = ClassBooking::where('class_occurrence_id', $occurrence->id)
                ->where('member_id', $member->id)
                ->whereIn('status', ['confirmed', 'waitlisted'])
                ->with('member')
                ->firstOrFail();

            return (new ClassBookingResource($existing))->response()->setStatusCode(200);
        }

        [$message, $code, $status] = match ($result->failure) {
            EnrollFailure::NotOpenYet => ['Le prenotazioni non sono ancora aperte.', 'booking_not_open', 422],
            EnrollFailure::BookingClosed => ['Prenotazioni chiuse.', 'booking_closed', 422],
            EnrollFailure::OccurrenceNotEnrollable => ["L'occorrenza non è disponibile per prenotazione.", 'occurrence_not_enrollable', 422],
            EnrollFailure::NoSubscription => ['Nessun abbonamento attivo.', 'subscription_inactive', 422],
            EnrollFailure::NoCert => ['Certificato medico scaduto o mancante.', 'cert_invalid', 422],
            EnrollFailure::AthleteOverlap => ["L'atleta ha già un corso confermato in questo orario.", 'athlete_overlap', 409],
            EnrollFailure::PtOverlap => ["L'atleta ha già una sessione PT confermata in questo orario.", 'pt_overlap', 409],
            default => throw new \LogicException('EnrollFailure non gestito: '.var_export($result->failure, true)),
        };

        return response()->json(['message' => $message, 'code' => $code], $status);
    }

    public function destroy(ClassBooking $booking): JsonResponse
    {
        if (! Setting::bool('group_classes_enabled', false)) {
            return $this->moduleDisabledResponse();
        }

        $failure = app(ClassBookingService::class)->cancel($booking, byGym: false);

        if ($failure === null) {
            return response()->json(null, 204);
        }

        [$message, $code] = match ($failure) {
            CancelFailure::DeadlineExceeded => ['Cancellazione non disponibile oltre la deadline gratuita.', 'cancel_deadline_exceeded'],
            CancelFailure::NotCancellable => ['La prenotazione non è in uno stato cancellabile.', 'booking_not_cancellable'],
        };

        return response()->json(['message' => $message, 'code' => $code], 409);
    }
}
