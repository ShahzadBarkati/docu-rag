<?php

namespace App\Services;

use App\Ai\Time\CurrentDateTimeProvider;
use App\Ai\Tools\CalculateDateOffset;
use App\Ai\Tools\FormatDateTime;
use App\Ai\Tools\GetCurrentDateTime;
use App\Ai\Tools\GetUserTimezone;
use App\Models\Conversation;
use App\Models\Document;
use App\Services\Documents\EmbeddingService;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Responses\StreamableAgentResponse;
use Laravel\Ai\Responses\StreamedAgentResponse;

use function Laravel\Ai\agent;

class ChatService
{
    public function __construct(
        private readonly EmbeddingService $embeddingService,
        private readonly CurrentDateTimeProvider $time,
    ) {}

    public function chat(Conversation $conversation, string $userMessage, ?int $documentId = null, $user = null): string
    {
        $response = $this->agent($conversation, $userMessage, $documentId, $user)->prompt($userMessage);

        return $response->text ?? 'Sorry, I could not generate a response.';
    }

    public function stream(Conversation $conversation, string $userMessage, ?int $documentId = null, $user = null): StreamableAgentResponse
    {
        $stream = $this->agent($conversation, $userMessage, $documentId, $user)->stream($userMessage);

        $stream->then(function (StreamedAgentResponse $response) use ($conversation): void {
            $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $response->text,
            ]);
            $conversation->touch();
        });

        return $stream;
    }

    private function agent(Conversation $conversation, string $userMessage, ?int $documentId = null, $user = null): Agent
    {
        $queryEmbedding = $this->embeddingService->embed($userMessage);
        $embeddingStr = '['.implode(',', $queryEmbedding).']';

        $query = DB::table('document_chunks')
            ->join('documents', 'document_chunks.document_id', '=', 'documents.id')
            ->where('documents.user_id', $conversation->user_id)
            ->selectRaw(
                'document_chunks.content, documents.name as document_name, 1 - (embedding <=> ?::vector) as similarity',
                [$embeddingStr],
            )
            ->whereRaw('(embedding <=> ?::vector) < 0.4', [$embeddingStr])
            ->orderBy('similarity', 'desc')
            ->limit(5);

        if ($documentId !== null) {
            $document = Document::where('id', $documentId)
                ->where('user_id', $conversation->user_id)
                ->first();

            if (! $document) {
                return agent(instructions: 'You can only answer based on documents that belong to you. Inform the user that the selected document could not be found or that they do not have access to it.');
            }

            $query->where('documents.id', $documentId);
        }

        $chunks = $query->get();

        $context = $this->buildContext($chunks);

        $systemPrompt = <<<'PROMPT'
You are a helpful assistant that answers questions based on the provided document context.
Rules:
1. Answer based on the provided context. If a chunk is clearly relevant to the question, answer it directly and cite the source. If no provided chunk is relevant to the question, answer only "I don't have enough information in the documents to answer that."
2. Cite your sources using [1], [2], etc. format matching the context references.
3. Be concise and accurate.
4. If the question is unclear, ask for clarification.
5. You may only use the date/time tools when asked about the current date or time, or about formatting or computing with dates and times. Never use them to answer questions that are about the documents.
PROMPT;

        $fullPrompt = $systemPrompt."\n\nContext:\n{$context}";

        return agent(
            instructions: $fullPrompt,
            messages: $this->buildHistory($conversation),
            tools: $this->tools($conversation),
        );
    }

    /**
     * Build the tools available to the chat agent.
     *
     * @return array<int, Tool>
     */
    private function tools(Conversation $conversation): array
    {
        $userTimezone = $conversation->user?->timezone ?? null;

        return [
            new GetCurrentDateTime($this->time),
            new GetUserTimezone($this->time, $userTimezone),
            new CalculateDateOffset($this->time),
            new FormatDateTime($this->time),
        ];
    }

    private function buildHistory(Conversation $conversation): array
    {
        $messages = $conversation->messages()
            ->orderBy('id')
            ->get()
            ->slice(0, -1)
            ->take(-8);

        $history = [];
        foreach ($messages as $message) {
            $history[] = new Message($message->role, $message->content);
        }

        return $history;
    }

    private function buildContext($chunks): string
    {
        if ($chunks->isEmpty()) {
            return 'No relevant context found in the documents.';
        }

        $context = '';
        foreach ($chunks as $i => $chunk) {
            $context .= sprintf(
                "[%d] (Source: %s, Similarity: %.1f%%)\n%s\n\n",
                $i + 1,
                $chunk->document_name,
                $chunk->similarity * 100,
                $chunk->content
            );
        }

        return $context;
    }
}
