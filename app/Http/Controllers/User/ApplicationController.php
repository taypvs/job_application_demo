<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\Applications\ApplicationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function __construct(private readonly ApplicationService $applications) {}

    public function index(Request $request): View
    {
        return view('user.applications.index', [
            'applications' => $this->applications->forCandidate($request->user()),
        ]);
    }
}
