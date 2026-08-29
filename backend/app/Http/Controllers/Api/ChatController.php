<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\SendMessageRequest;
use App\Http\Requests\UpdateConversationRequest;
use App\Models\Conversation;
use App\Services\ChatService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Streaming\Events\TextDelta;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ChatController
{
    public function __construct(
        private readonly ChatService $chatService,
    ) {}

    public function store(SendMessageRequest $request): Response
    {
        $validated = $request->validated();
        $user = $request->user();
        if (! empty($validated['conversation_id'])) {
            $conversation = Conversation::where('id', $validated['conversation_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();
        } else {
            $conversation = Conversation::create([
                'user_id' => $user->id,
                'title' => mb_substr($validated['message'], 0, 50),
            ]);
        }
        $conversation->messages()->create([
            'role' => 'user',
            'content' => $validated['message'],
        ]);

        try {
            $stream = $this->chatService->stream(
                $conversation,
                $validated['message'],
                $validated['document_id'] ?? null,
                $user,
            );

            return response()->stream(function () use ($stream, $conversation): void {
                echo 'data: '.json_encode([
                    'type' => 'conversation',
                    'conversation_id' => $conversation->id,
                ])."\n\n";
                $this->flush();

                try {
                    $stream->each(function ($event): void {
                        if (! $event instanceof TextDelta) {
                            return;
                        }
                        echo 'data: '.json_encode([
                            'type' => 'text_delta',
                            'message_id' => $event->messageId,
                            'delta' => $event->delta,
                        ])."\n\n";
                        $this->flush();
                    });

                    echo "data: [DONE]\n\n";
                    $this->flush();
                } catch (Throwable $e) {
                    Log::error('Chat stream failed', [
                        'conversation_id' => $conversation->id,
                        'error' => $e->getMessage(),
                    ]);

                    echo 'data: '.json_encode([
                        'type' => 'error',
                        'message' => 'Failed to generate response. Please try again.',
                    ])."\n\n";
                    echo "data: [DONE]\n\n";
                    $this->flush();
                }
            }, headers: [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
            ]);
        } catch (Throwable $e) {
            Log::error('Chat failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('Failed to generate response: '.$e->getMessage(), 500);
        }
    }

    private function flush(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    public function conversations(Request $request): JsonResponse
    {
        $conversations = Conversation::where('user_id', $request->user()->id)
            ->orderBy('updated_at', 'desc')
            ->get(['id', 'title', 'created_at', 'updated_at']);

        return ApiResponse::success($conversations);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);
        $conversation->load('messages');

        return ApiResponse::success($conversation);
    }

    public function update(UpdateConversationRequest $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('update', $conversation);
        $conversation->update(['title' => $request->validated('title')]);

        return ApiResponse::success($conversation->only(['id', 'title', 'updated_at']));
    }

    public function destroy(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('delete', $conversation);
        $conversation->delete();

        return ApiResponse::success(null, 'Conversation deleted');
    }
}
