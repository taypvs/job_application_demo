<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ChatMessageRequest;
use App\Services\Chat\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(private readonly ChatbotService $chatbot) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'messages' => $this->chatbot->history($request->user()),
        ]);
    }

    public function store(ChatMessageRequest $request): JsonResponse
    {
        return response()->json([
            'reply' => $this->chatbot->reply($request->user(), $request->validated('message')),
        ]);
    }
}
