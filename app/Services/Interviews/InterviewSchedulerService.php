<?php

namespace App\Services\Interviews;

use App\Enums\ApplicationStatus;
use App\Enums\InterviewStatus;
use App\Models\Application;
use App\Models\Interview;
use App\Models\Manager;
use App\Services\Mailbox\MailboxService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Finds the earliest free interview slot from the hiring department's managers.
 * The rules are fixed (working windows minus existing bookings), so this stays
 * ordinary application code instead of an LLM agent.
 */
class InterviewSchedulerService
{
    public function __construct(private readonly MailboxService $mailbox) {}

    public function assign(Application $application): Interview
    {
        if (in_array($application->status, [ApplicationStatus::Passed, ApplicationStatus::Rejected], true)) {
            throw new RuntimeException(__('messages.interviews.closed'));
        }

        if ($application->interview()->exists()) {
            throw new RuntimeException(__('messages.interviews.already_set'));
        }

        $application->loadMissing('jobPosting.department');
        $departmentId = $application->jobPosting?->department_id;
        if (! $departmentId) {
            throw new RuntimeException(__('messages.interviews.no_department'));
        }

        $managers = Manager::query()
            ->with('availabilities')
            ->where('department_id', $departmentId)
            ->orderBy('id')
            ->get();

        if ($managers->isEmpty()) {
            throw new RuntimeException(__('messages.interviews.no_manager'));
        }

        $slot = $this->earliestSlot($managers);
        if ($slot === null) {
            throw new RuntimeException(__('messages.interviews.no_slot'));
        }

        return DB::transaction(function () use ($application, $slot) {
            $overlaps = Interview::query()
                ->where('manager_id', $slot['manager']->id)
                ->where('status', InterviewStatus::Scheduled)
                ->where('starts_at', '<', $slot['end'])
                ->where('ends_at', '>', $slot['start'])
                ->lockForUpdate()
                ->exists();

            if ($overlaps) {
                throw new RuntimeException(__('messages.interviews.slot_taken'));
            }

            $interview = Interview::create([
                'application_id' => $application->id,
                'manager_id' => $slot['manager']->id,
                'starts_at' => $slot['start'],
                'ends_at' => $slot['end'],
                'format' => 'オンライン',
                'status' => InterviewStatus::Scheduled,
            ]);

            $application->update(['status' => ApplicationStatus::Interview]);
            $this->mailbox->notifyInterview($application->fresh(), $interview);

            return $interview->load('manager.department');
        });
    }

    /**
     * @param  Collection<int, Manager>  $managers
     * @return array{manager: Manager, start: Carbon, end: Carbon}|null
     */
    private function earliestSlot(Collection $managers): ?array
    {
        $duration = (int) config('recruiting.interview_minutes');
        $step = (int) config('recruiting.interview_step_minutes');
        $days = (int) config('recruiting.interview_search_days');
        $searchStart = now()->addDay()->startOfDay();
        $searchEnd = now()->addDays($days)->endOfDay();

        $existing = Interview::query()
            ->whereIn('manager_id', $managers->pluck('id'))
            ->where('status', InterviewStatus::Scheduled)
            ->where('starts_at', '<', $searchEnd)
            ->where('ends_at', '>', $searchStart)
            ->get()
            ->groupBy('manager_id');

        $best = null;

        foreach ($managers as $manager) {
            $cursor = $searchStart->copy();
            while ($cursor->lte($searchEnd)) {
                if ($cursor->isWeekend()) {
                    $cursor->addDay()->startOfDay();

                    continue;
                }

                $windows = $manager->availabilities
                    ->where('weekday', $cursor->dayOfWeekIso)
                    ->sortBy('start_time');

                foreach ($windows as $window) {
                    $slotStart = $cursor->copy()->setTimeFromTimeString((string) $window->start_time);
                    $windowEnd = $cursor->copy()->setTimeFromTimeString((string) $window->end_time);

                    while ($slotStart->copy()->addMinutes($duration)->lte($windowEnd)) {
                        $slotEnd = $slotStart->copy()->addMinutes($duration);
                        $busy = $existing->get($manager->id, collect());
                        $overlaps = $busy->contains(function (Interview $interview) use ($slotStart, $slotEnd) {
                            return $interview->starts_at->lt($slotEnd) && $interview->ends_at->gt($slotStart);
                        });

                        if ($slotStart->isFuture() && ! $overlaps) {
                            if ($best === null || $slotStart->lt($best['start'])) {
                                $best = [
                                    'manager' => $manager,
                                    'start' => $slotStart->copy(),
                                    'end' => $slotEnd->copy(),
                                ];
                            }

                            break 3;
                        }

                        $slotStart->addMinutes($step);
                    }
                }

                $cursor->addDay()->startOfDay();
            }
        }

        return $best;
    }
}
