<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class KpiService
{
    private const TTL = 3600;

    /** @return array<string, int> */
    public function revenueByPeriod(Carbon $from, Carbon $to): array
    {
        $key = "kpi:revenueByPeriod:{$from->toDateString()}:{$to->toDateString()}";

        return Cache::tags(['kpi'])->remember($key, self::TTL, function () use ($from, $to) {
            $driver = DB::getDriverName();
            $monthExpr = $driver === 'sqlite'
                ? "strftime('%Y-%m', s.started_at)"
                : "DATE_FORMAT(s.started_at, '%Y-%m')";

            $rows = DB::table('subscriptions as s')
                ->join('subscription_plans as sp', 'sp.id', '=', 's.plan_id')
                ->whereBetween('s.started_at', [$from->toDateString(), $to->toDateString()])
                ->selectRaw("{$monthExpr} as month, SUM(sp.price_cents) as total")
                ->groupByRaw($monthExpr)
                ->orderBy('month')
                ->get();

            $result = [];
            foreach ($rows as $row) {
                $result[$row->month] = (int) $row->total;
            }

            return $result;
        });
    }

    /** @return list<array{plan: string, revenue_cents: int, count: int}> */
    public function revenueByPlan(Carbon $from, Carbon $to): array
    {
        $key = "kpi:revenueByPlan:{$from->toDateString()}:{$to->toDateString()}";

        return Cache::tags(['kpi'])->remember($key, self::TTL, function () use ($from, $to) {
            $rows = DB::table('subscriptions as s')
                ->join('subscription_plans as sp', 'sp.id', '=', 's.plan_id')
                ->whereBetween('s.started_at', [$from->toDateString(), $to->toDateString()])
                ->select('sp.name as plan', DB::raw('SUM(sp.price_cents) as revenue_cents'), DB::raw('COUNT(*) as cnt'))
                ->groupBy('sp.id', 'sp.name')
                ->orderByDesc('revenue_cents')
                ->get();

            $result = [];
            foreach ($rows as $row) {
                $result[] = [
                    'plan' => $row->plan,
                    'revenue_cents' => (int) $row->revenue_cents,
                    'count' => (int) $row->cnt,
                ];
            }

            return $result;
        });
    }

    /** @return list<array{trainer: string, revenue_cents: int, member_count: int}> */
    public function revenueByTrainer(Carbon $from, Carbon $to): array
    {
        $key = "kpi:revenueByTrainer:{$from->toDateString()}:{$to->toDateString()}";

        return Cache::tags(['kpi'])->remember($key, self::TTL, function () use ($from, $to) {
            $rows = DB::table('users as u')
                ->join('mesocycles as mc', 'mc.trainer_id', '=', 'u.id')
                ->join('members as m', function ($j) {
                    $j->on('m.user_id', '=', 'mc.athlete_id');
                })
                ->join('subscriptions as s', 's.member_id', '=', 'm.id')
                ->join('subscription_plans as sp', 'sp.id', '=', 's.plan_id')
                ->where('mc.start_date', '<=', $to->toDateString())
                ->whereBetween('s.started_at', [$from->toDateString(), $to->toDateString()])
                ->select(
                    'u.id as trainer_id',
                    'u.name as trainer',
                    DB::raw('SUM(sp.price_cents) as revenue_cents'),
                    DB::raw('COUNT(DISTINCT m.id) as member_count'),
                )
                ->groupBy('u.id', 'u.name')
                ->orderByDesc('revenue_cents')
                ->get();

            $result = [];
            foreach ($rows as $row) {
                $result[] = [
                    'trainer' => $row->trainer,
                    'revenue_cents' => (int) $row->revenue_cents,
                    'member_count' => (int) $row->member_count,
                ];
            }

            return $result;
        });
    }

    public function retentionRate(Carbon $from, Carbon $to): float
    {
        $key = "kpi:retentionRate:{$from->toDateString()}:{$to->toDateString()}";

        return Cache::tags(['kpi'])->remember($key, self::TTL, function () use ($from, $to) {
            $fromDate = $from->toDateString();
            $toDate = $to->toDateString();

            $activeAtStart = DB::table('subscriptions')
                ->where('started_at', '<=', $fromDate)
                ->where('expires_at', '>=', $fromDate)
                ->distinct()
                ->pluck('member_id')
                ->all();

            if (empty($activeAtStart)) {
                return 0.0;
            }

            $stillActive = DB::table('subscriptions')
                ->whereIn('member_id', $activeAtStart)
                ->where('started_at', '<=', $toDate)
                ->where('expires_at', '>=', $toDate)
                ->distinct('member_id')
                ->count('member_id');

            return round(($stillActive / count($activeAtStart)) * 100, 1);
        });
    }

    public function churnRate(Carbon $from, Carbon $to): float
    {
        $key = "kpi:churnRate:{$from->toDateString()}:{$to->toDateString()}";

        return Cache::tags(['kpi'])->remember($key, self::TTL, function () use ($from, $to) {
            $fromDate = $from->toDateString();
            $toDate = $to->toDateString();

            $expired = DB::table('subscriptions')
                ->whereBetween('expires_at', [$fromDate, $toDate])
                ->select('id', 'member_id', 'expires_at')
                ->get();

            if ($expired->isEmpty()) {
                return 0.0;
            }

            $churned = 0;
            foreach ($expired as $sub) {
                $expiredDate = Carbon::parse($sub->expires_at);
                $renewalDeadline = $expiredDate->copy()->addDays(30)->toDateString();

                $renewed = DB::table('subscriptions')
                    ->where('member_id', $sub->member_id)
                    ->where('id', '!=', $sub->id)
                    ->where('started_at', '>', $sub->expires_at)
                    ->where('started_at', '<=', $renewalDeadline)
                    ->exists();

                if (! $renewed) {
                    $churned++;
                }
            }

            return round(($churned / $expired->count()) * 100, 1);
        });
    }

    public function newMembersCount(Carbon $from, Carbon $to): int
    {
        $key = "kpi:newMembersCount:{$from->toDateString()}:{$to->toDateString()}";

        return Cache::tags(['kpi'])->remember($key, self::TTL, fn () => DB::table('members')
            ->whereBetween('created_at', [$from->toDateTimeString(), $to->toDateTimeString()])
            ->count());
    }

    public function activeMembersCount(Carbon $from, Carbon $to): int
    {
        $key = "kpi:activeMembersCount:{$from->toDateString()}:{$to->toDateString()}";

        return Cache::tags(['kpi'])->remember($key, self::TTL, fn () => DB::table('subscriptions')
            ->where('started_at', '<=', $to->toDateString())
            ->where('expires_at', '>=', $from->toDateString())
            ->distinct('member_id')
            ->count('member_id'));
    }

    public function avgSessionsPerMember(Carbon $from, Carbon $to): float
    {
        $key = "kpi:avgSessionsPerMember:{$from->toDateString()}:{$to->toDateString()}";

        return Cache::tags(['kpi'])->remember($key, self::TTL, function () use ($from, $to) {
            $activeMemberIds = DB::table('subscriptions')
                ->where('started_at', '<=', $to->toDateString())
                ->where('expires_at', '>=', $from->toDateString())
                ->distinct()
                ->pluck('member_id')
                ->all();

            if (empty($activeMemberIds)) {
                return 0.0;
            }

            $rows = DB::table('training_sessions as ts')
                ->join('microcycle_weeks as mw', 'mw.id', '=', 'ts.microcycle_week_id')
                ->join('mesocycles as mc', 'mc.id', '=', 'mw.mesocycle_id')
                ->join('members as m', 'm.user_id', '=', 'mc.athlete_id')
                ->whereIn('m.id', $activeMemberIds)
                ->where('ts.status', 'completed')
                ->whereBetween('ts.completed_at', [$from->toDateTimeString(), $to->toDateTimeString()])
                ->select('m.id as member_id', DB::raw('COUNT(ts.id) as session_count'))
                ->groupBy('m.id')
                ->get();

            if ($rows->isEmpty()) {
                return 0.0;
            }

            return round($rows->avg('session_count'), 1);
        });
    }

    /** @return list<array{trainer: string, slots_available: int, slots_booked: int, occupancy_pct: float}> */
    public function trainerOccupancy(Carbon $from, Carbon $to): array
    {
        $key = "kpi:trainerOccupancy:{$from->toDateString()}:{$to->toDateString()}";

        return Cache::tags(['kpi'])->remember($key, self::TTL, function () use ($from, $to) {
            $daysInPeriod = array_fill(0, 7, 0);
            $cursor = $from->copy()->startOfDay();
            $end = $to->copy()->startOfDay();
            while ($cursor->lte($end)) {
                $dow = $cursor->dayOfWeekIso - 1;
                $daysInPeriod[$dow]++;
                $cursor->addDay();
            }

            $trainerIds = DB::table('trainer_availability')
                ->distinct()
                ->pluck('trainer_id')
                ->all();

            if (empty($trainerIds)) {
                return [];
            }

            $trainers = DB::table('users')
                ->whereIn('id', $trainerIds)
                ->select('id', 'name')
                ->get();

            $result = [];
            foreach ($trainers as $trainer) {
                $recurringSlots = DB::table('trainer_availability')
                    ->where('trainer_id', $trainer->id)
                    ->whereNotNull('day_of_week')
                    ->where('is_available', true)
                    ->pluck('day_of_week')
                    ->all();

                $slotsAvailable = 0;
                foreach ($recurringSlots as $dow) {
                    $slotsAvailable += $daysInPeriod[(int) $dow] ?? 0;
                }

                $slotsAvailable += DB::table('trainer_availability')
                    ->where('trainer_id', $trainer->id)
                    ->whereNotNull('specific_date')
                    ->where('is_available', true)
                    ->whereBetween('specific_date', [$from->toDateString(), $to->toDateString()])
                    ->count();

                $blockedDates = DB::table('trainer_availability')
                    ->where('trainer_id', $trainer->id)
                    ->whereNotNull('specific_date')
                    ->where('is_available', false)
                    ->whereBetween('specific_date', [$from->toDateString(), $to->toDateString()])
                    ->count();

                $slotsAvailable = max(0, $slotsAvailable - $blockedDates);

                $slotsBooked = DB::table('pt_bookings')
                    ->where('trainer_id', $trainer->id)
                    ->where('status', 'completed')
                    ->whereBetween('booked_date', [$from->toDateString(), $to->toDateString()])
                    ->count();

                $occupancyPct = $slotsAvailable > 0
                    ? round(($slotsBooked / $slotsAvailable) * 100, 1)
                    : 0.0;

                $result[] = [
                    'trainer' => $trainer->name,
                    'slots_available' => $slotsAvailable,
                    'slots_booked' => $slotsBooked,
                    'occupancy_pct' => $occupancyPct,
                ];
            }

            return $result;
        });
    }
}
