<?php

namespace App\Services\Interviews;

use App\Models\Interview;
use Carbon\Carbon;

class InterviewCalendarService
{
    /**
     * @return list<list<array{date: Carbon, in_month: bool, interviews: \Illuminate\Support\Collection}>>
     */
    public function build(Carbon $month): array
    {
        $start = $month->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $end = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $grouped = Interview::query()
            ->with(['manager', 'application.user', 'application.jobPosting'])
            ->where('starts_at', '>=', $start->copy()->startOfDay())
            ->where('starts_at', '<=', $end->copy()->endOfDay())
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (Interview $interview) => $interview->starts_at->toDateString());

        $weeks = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = [
                    'date' => $cursor->copy(),
                    'in_month' => $cursor->month === $month->month,
                    'interviews' => $grouped->get($cursor->toDateString(), collect()),
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }
}
