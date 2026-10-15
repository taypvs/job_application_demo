<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UploadDocumentRequest;
use App\Models\KnowledgeDocument;
use App\Services\Knowledge\KnowledgeIngestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

class DocumentController extends Controller
{
    public function __construct(private readonly KnowledgeIngestService $ingest) {}

    public function index(): View
    {
        return view('admin.documents.index', [
            'documents' => KnowledgeDocument::query()->latest('id')->paginate(15),
        ]);
    }

    public function store(UploadDocumentRequest $request): RedirectResponse
    {
        try {
            $document = $this->ingest->ingest(
                $request->user(),
                $request->safe()->only(['title', 'source_type', 'url']),
                $request->file('file'),
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['file' => $e->getMessage()]);
        }

        $message = $document->status->value === 'indexed'
            ? __('messages.documents.indexed')
            : __('messages.documents.saved');

        return redirect()->route('admin.documents.index')->with('status', $message);
    }

    public function destroy(KnowledgeDocument $knowledgeDocument): RedirectResponse
    {
        try {
            $this->ingest->destroy($knowledgeDocument);
        } catch (RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        return redirect()->route('admin.documents.index')->with('status', __('messages.documents.deleted'));
    }
}
