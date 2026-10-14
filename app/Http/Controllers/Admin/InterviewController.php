<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Interviews\InterviewCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InterviewController extends Controller
{
    public function __construct(private readonly InterviewCalendarService $calendar) {}

    public function index(Request $request): View
    {
        $raw = (string) $request->query('month', now()->format('Y-m'));
        $month = preg_match('/^\d{4}-\d{2}$/', $raw)
            ? Carbon::createFromFormat('!Y-m', $raw)->startOfMonth()
            : now()->startOfMonth();

        return view('admin.interviews.index', [
            'month' => $month,
            'weeks' => $this->calendar->build($month),
            'prev' => $month->copy()->subMonth()->format('Y-m'),
            'next' => $month->copy()->addMonth()->format('Y-m'),
        ]);
    }
}
