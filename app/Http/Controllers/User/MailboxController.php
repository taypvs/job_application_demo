<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\MailboxMessage;
use App\Services\Mailbox\MailboxService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MailboxController extends Controller
{
    public function __construct(private readonly MailboxService $mailbox) {}

    public function index(Request $request): View
    {
        return view('user.mailbox.index', [
            'messages' => $this->mailbox->paginate($request->user()),
        ]);
    }

    public function show(Request $request, MailboxMessage $mailboxMessage): View
    {
        return view('user.mailbox.show', [
            'message' => $this->mailbox->open($request->user(), $mailboxMessage),
        ]);
    }
}
