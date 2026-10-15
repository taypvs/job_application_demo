<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UploadCvRequest;
use App\Models\Cv;
use App\Services\Cv\CvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CvController extends Controller
{
    public function __construct(private readonly CvService $cvs) {}

    public function index(Request $request): View
    {
        return view('user.cv.index', [
            'cv' => $request->user()->currentCv(),
        ]);
    }

    public function store(UploadCvRequest $request): RedirectResponse
    {
        try {
            $cv = $this->cvs->store($request->user(), $request->file('file'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $message = $cv->extracted_text
            ? ($cv->parse_engine === 'openai'
                ? __('messages.cv.saved_ai')
                : __('messages.cv.saved_local'))
            : __('messages.cv.saved_no_text');

        return back()->with('status', $message);
    }

    public function download(Request $request, Cv $cv): StreamedResponse
    {
        return $this->cvs->download($request->user(), $cv);
    }
}
