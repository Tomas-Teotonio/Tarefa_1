<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\AiPrompt;
use App\Services\OpenRouterService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\AiFileContextExtractor;

class AiChatController extends Controller
{
    public function index(OpenRouterService $openRouter)
    {
        $user = Auth::user();

        $conversations = $this->sidebarConversations($user->id);

        $modelsResult = $openRouter->models();

        return Inertia::render('AiChat/Index', [
            'activeConversation' => null,
            'conversations' => $conversations,
            'models' => $modelsResult['models'],
            'openRouterConfigured' => $openRouter->isConfigured(),
            'modelsError' => $modelsResult['error'],
            'prompts' => AiPrompt::query()
                ->where('user_id', $user->id)
                ->orderBy('name')
                ->get(['id', 'name', 'content', 'created_at', 'updated_at']),
        ]);
    }

    public function show(AiConversation $conversation, OpenRouterService $openRouter)
    {
        $this->authorizeConversation($conversation);

        $conversation->load([
            'messages' => fn ($query) => $query
                ->orderBy('created_at')
                ->with([
                    'comments' => fn ($query) => $query
                        ->where('user_id', Auth::id())
                        ->orderBy('created_at')
                        ->select([
                            'id',
                            'ai_message_id',
                            'content',
                            'created_at',
                            'updated_at',
                        ]),
                ]),

            'shares' => fn ($query) => $query
                ->whereNull('revoked_at')
                ->orderBy('visibility')
                ->get([
                    'id',
                    'ai_conversation_id',
                    'visibility',
                    'token',
                    'created_at',
                ]),
        ]);

        $conversation->shares->transform(function ($share) {
            return [
                'id' => $share->id,
                'visibility' => $share->visibility,
                'url' => route('ai-chat.shared.show', $share->token),
                'created_at' => $share->created_at,
            ];
        });

        $conversations = $this->sidebarConversations(Auth::id());

        $modelsResult = $openRouter->models();

        return Inertia::render('AiChat/Index', [
            'activeConversation' => $conversation,
            'conversations' => $conversations,
            'models' => $modelsResult['models'],
            'openRouterConfigured' => $openRouter->isConfigured(),
            'modelsError' => $modelsResult['error'],
            'prompts' => AiPrompt::query()
                ->where('user_id', Auth::id())
                ->orderBy('name')
                ->get(['id', 'name', 'content', 'created_at', 'updated_at']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
            'model_id' => ['required', 'string', 'max:255'],
            'temperature' => ['required', 'numeric', 'min:0', 'max:2'],
            'max_tokens' => ['required', 'integer', 'min:1', 'max:8000'],
        ]);

        $conversation = AiConversation::create([
            'user_id' => Auth::id(),
            'title' => $this->generateTitle($data['message']),
            'model_id' => $data['model_id'],
            'temperature' => $data['temperature'],
            'max_tokens' => $data['max_tokens'],
        ]);

        $conversation->messages()->create([
            'role' => 'user',
            'content' => $data['message'],
            'model_id' => $data['model_id'],
        ]);

        return redirect()->route('ai-chat.show', $conversation);
    }

    public function storeMessage(Request $request, AiConversation $conversation)
    {
        $this->authorizeConversation($conversation);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
            'model_id' => ['required', 'string', 'max:255'],
            'temperature' => ['required', 'numeric', 'min:0', 'max:2'],
            'max_tokens' => ['required', 'integer', 'min:1', 'max:8000'],
        ]);

        $conversation->update([
            'model_id' => $data['model_id'],
            'temperature' => $data['temperature'],
            'max_tokens' => $data['max_tokens'],
        ]);

        $conversation->messages()->create([
            'role' => 'user',
            'content' => $data['message'],
            'model_id' => $data['model_id'],
        ]);

        return redirect()->route('ai-chat.show', $conversation);
    }

    public function updateSettings(Request $request, AiConversation $conversation)
    {
        $this->authorizeConversation($conversation);

        $data = $request->validate([
            'model_id' => ['required', 'string', 'max:255'],
            'temperature' => ['required', 'numeric', 'min:0', 'max:2'],
            'max_tokens' => ['required', 'integer', 'min:1', 'max:8000'],
        ]);

        $conversation->update($data);

        return back()->with('success', 'Configurações atualizadas.');
    }

    private function sidebarConversations(int $userId)
    {
        return AiConversation::query()
            ->where('user_id', $userId)
            ->orderByRaw('pinned_at IS NULL')
            ->orderByDesc('pinned_at')
            ->orderByDesc('updated_at')
            ->get([
                'id',
                'title',
                'model_id',
                'temperature',
                'max_tokens',
                'pinned_at',
                'created_at',
                'updated_at',
            ]);
    }

    private function authorizeConversation(AiConversation $conversation): void
    {
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function generateTitle(string $message): string
    {
        $title = trim(strip_tags($message));

        $title = preg_replace('/\s+/', ' ', $title);

        return mb_strlen($title) > 60
            ? mb_substr($title, 0, 60) . '...'
            : $title;
    }

    public function inspectAttachment(
        Request $request,
        AiFileContextExtractor $fileExtractor
    ): JsonResponse {
        $request->validate([
            'attachment' => [
                'required',
                'file',
                'max:5120',
                'extensions:txt,md,pdf,js,ts,php,py,html,css,json,xml,csv',
            ],
        ], [
            'attachment.required' => 'Seleciona um ficheiro.',
            'attachment.max' => 'O ficheiro não pode ter mais de 5 MB.',
            'attachment.extensions' => 'Tipo de ficheiro não permitido.',
        ]);

        try {
            $extractedFile = $fileExtractor->extract(
                $request->file('attachment')
            );
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => [
                    'attachment' => [$e->getMessage()],
                ],
            ], 422);
        }

        return response()->json([
            'file_name' => $extractedFile['file_name'],
            'extension' => $extractedFile['extension'],

            'characters' => $extractedFile['characters'],
            'estimated_tokens' => $extractedFile['estimated_tokens'],

            'original_characters' => $extractedFile['original_characters'],
            'original_estimated_tokens' => $extractedFile['original_estimated_tokens'],

            'was_truncated' => $extractedFile['was_truncated'],
        ]);
    }

    public function stream(
        Request $request,
        OpenRouterService $openRouter,
        AiFileContextExtractor $fileExtractor
    ) {
        if (!$openRouter->isConfigured()) {
            return response()->json([
                'message' => 'A chave da API OpenRouter não está configurada.',
            ], 422);
        }

        $data = $request->validate([
            'conversation_id' => ['nullable', 'integer', 'exists:ai_conversations,id'],
            'message' => ['required', 'string', 'max:10000'],
            'model_id' => ['required', 'string', 'max:255'],
            'temperature' => ['required', 'numeric', 'min:0', 'max:2'],
            'max_tokens' => ['required', 'integer', 'min:1', 'max:8000'],
            'attachment' => [
                'nullable',
                'file',
                'max:5120',
                'extensions:txt,md,pdf,js,ts,php,py,html,css,json,xml,csv',
            ],
        ], [
            'attachment.max' => 'O ficheiro não pode ter mais de 5 MB.',
            'attachment.extensions' => 'Tipo de ficheiro não permitido.',
        ]);

        $fileContext = null;
        $fileName = null;

        if ($request->hasFile('attachment')) {
            try {
                $extractedFile = $fileExtractor->extract($request->file('attachment'));

                $fileContext = $extractedFile['context'];
                $fileName = $extractedFile['file_name'];
            } catch (\Throwable $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => [
                        'attachment' => [$e->getMessage()],
                    ],
                ], 422);
            }
        }

        $userVisibleMessage = $data['message'];

        $messageForModel = $fileContext
            ? $userVisibleMessage . "\n\n---\n\n" . $fileContext
            : $userVisibleMessage;

        if (!empty($data['conversation_id'])) {
            $conversation = AiConversation::findOrFail($data['conversation_id']);

            $this->authorizeConversation($conversation);

            $conversation->update([
                'model_id' => $data['model_id'],
                'temperature' => $data['temperature'],
                'max_tokens' => $data['max_tokens'],
            ]);
        } else {
            $conversation = AiConversation::create([
                'user_id' => Auth::id(),
                'title' => $this->generateTitle($userVisibleMessage),
                'model_id' => $data['model_id'],
                'temperature' => $data['temperature'],
                'max_tokens' => $data['max_tokens'],
            ]);
        }

        $userMessage = $conversation->messages()->create([
            'role' => 'user',
            'content' => $fileName
                ? $userVisibleMessage . "\n\n📎 Ficheiro anexado: " . $fileName
                : $userVisibleMessage,
            'model_id' => $data['model_id'],
        ]);

        $history = $conversation->messages()
            ->where('id', '!=', $userMessage->id)
            ->oldest()
            ->get()
            ->map(fn ($message) => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->toArray();

        $history[] = [
            'role' => 'user',
            'content' => $messageForModel,
        ];

        return response()->stream(function () use (
            $conversation,
            $userMessage,
            $history,
            $data,
            $openRouter
        ) {
            $this->prepareStream();

            $this->sendSse('conversation', [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'url' => route('ai-chat.show', $conversation),
                'user_message_id' => $userMessage->id,
            ]);

            try {
                $assistantContent = $openRouter->streamChat(
                    messages: $history,
                    modelId: $data['model_id'],
                    temperature: (float) $data['temperature'],
                    maxTokens: (int) $data['max_tokens'],
                    onDelta: function (string $delta) {
                        $this->sendSse('delta', [
                            'content' => $delta,
                        ]);
                    }
                );

                $assistantMessage = $conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => $assistantContent ?: 'Sem resposta do modelo.',
                    'model_id' => $data['model_id'],
                ]);

                $this->sendSse('done', [
                    'conversation_id' => $conversation->id,
                    'message_id' => $assistantMessage->id,
                    'url' => route('ai-chat.show', $conversation),
                ]);
            } catch (\Throwable $e) {
                Log::error('AI Chat stream error', [
                    'conversation_id' => $conversation->id,
                    'message' => $e->getMessage(),
                ]);

                $this->sendSse('error', [
                    'message' => $e->getMessage(),
                ]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function sendSse(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";

        if (ob_get_level() > 0) {
            @ob_flush();
        }

        flush();
    }

    private function prepareStream(): void
    {
        @ini_set('output_buffering', 'off');
        @ini_set('zlib.output_compression', '0');

        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
    }

    public function destroy(AiConversation $conversation)
    {
        $this->authorizeConversation($conversation);

        $conversation->delete();

        return redirect()
            ->route('ai-chat.index')
            ->with('success', 'Conversa apagada com sucesso.');
    }

    public function togglePin(AiConversation $conversation)
    {
        $this->authorizeConversation($conversation);

        if ($conversation->pinned_at) {
            $conversation->update([
                'pinned_at' => null,
            ]);

            return back()->with('success', 'Conversa desafixada com sucesso.');
        }

        $pinnedCount = AiConversation::query()
            ->where('user_id', request()->user()->id)
            ->whereNotNull('pinned_at')
            ->count();

        if ($pinnedCount >= 10) {
            return back()->withErrors([
                'pin' => 'Só podes ter até 10 conversas fixadas.',
            ]);
        }

        $conversation->update([
            'pinned_at' => now(),
        ]);

        return back()->with('success', 'Conversa fixada com sucesso.');
    }


    public function search(Request $request): JsonResponse
    {
        $term = trim(
            (string) $request->query('q', '')
        );

        if (mb_strlen($term) < 2) {
            return response()->json([
                'results' => [],
            ]);
        }

        $normalizedTerm = $this->normalizeSearchText($term);

        $conversations = AiConversation::query()
            ->with([
                'messages' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'ai_conversation_id',
                            'role',
                            'content',
                            'model_id',
                            'created_at',
                        ])
                        ->orderBy('created_at');
                },
            ])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->limit(200)
            ->get([
                'id',
                'title',
                'model_id',
                'pinned_at',
                'created_at',
                'updated_at',
            ]);

        $results = [];

        foreach ($conversations as $conversation) {
            $normalizedTitle = $this->normalizeSearchText(
                $conversation->title ?? ''
            );

            if (str_contains($normalizedTitle, $normalizedTerm)) {
                $results[] = [
                    'type' => 'conversation',
                    'conversation_id' => $conversation->id,
                    'message_id' => null,
                    'title' => $conversation->title,
                    'role' => null,
                    'excerpt' => 'Correspondência no título da conversa.',
                    'model_id' => $conversation->model_id,
                    'updated_at' => optional(
                        $conversation->updated_at
                    )->toISOString(),

                    'url' => route(
                        'ai-chat.show',
                        $conversation
                    ),

                    '_score' => $this->relevanceScore(
                        $normalizedTitle,
                        $normalizedTerm,
                        500
                    ),

                    '_timestamp' => optional(
                        $conversation->updated_at
                    )->timestamp ?? 0,
                ];
            }

            foreach ($conversation->messages as $message) {
                $normalizedContent = $this->normalizeSearchText(
                    $message->content ?? ''
                );

                if (!str_contains(
                    $normalizedContent,
                    $normalizedTerm
                )) {
                    continue;
                }

                $results[] = [
                    'type' => 'message',
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                    'title' => $conversation->title,
                    'role' => $message->role,

                    'excerpt' => $this->makeSearchExcerpt(
                        $message->content ?? '',
                        $term
                    ),

                    'model_id' => $message->model_id
                        ?? $conversation->model_id,

                    'updated_at' => optional(
                        $message->created_at
                    )->toISOString(),

                    'url' => route(
                        'ai-chat.show',
                        $conversation
                    )
                        . '?message=' . $message->id
                        . '&q=' . urlencode($term),

                    'search_term' => $term,

                    '_score' => $this->relevanceScore(
                        $normalizedContent,
                        $normalizedTerm,
                        100
                    ),

                    '_timestamp' => optional(
                        $message->created_at
                    )->timestamp ?? 0,
                ];

                if (count($results) >= 250) {
                    break 2;
                }
            }
        }

        $results = collect($results)
            ->sort(function (array $first, array $second) {
                $scoreComparison =
                    $second['_score'] <=> $first['_score'];

                if ($scoreComparison !== 0) {
                    return $scoreComparison;
                }

                return $second['_timestamp']
                    <=> $first['_timestamp'];
            })
            ->take(40)
            ->map(function (array $result) {
                unset(
                    $result['_score'],
                    $result['_timestamp']
                );

                return $result;
            })
            ->values()
            ->all();

        return response()->json([
            'results' => $results,
        ]);
    }

    private function normalizeSearchText(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->toString();
    }

    private function relevanceScore(
        string $normalizedText,
        string $normalizedTerm,
        int $baseScore
    ): int {
        $score = $baseScore;

        if ($normalizedText === $normalizedTerm) {
            $score += 400;
        } elseif (str_starts_with(
            $normalizedText,
            $normalizedTerm
        )) {
            $score += 250;
        }

        $occurrences = substr_count(
            $normalizedText,
            $normalizedTerm
        );

        $score += min($occurrences, 5) * 20;

        return $score;
    }

    private function makeSearchExcerpt(string $content, string $term, int $radius = 80): string
    {
        $normalizedContent = $this->normalizeSearchText($content);
        $normalizedTerm = $this->normalizeSearchText($term);

        $position = mb_strpos($normalizedContent, $normalizedTerm);

        if ($position === false) {
            return Str::limit($content, 180);
        }

        $start = max(0, $position - $radius);
        $length = mb_strlen($normalizedTerm) + ($radius * 2);

        $excerpt = mb_substr($content, $start, $length);

        return ($start > 0 ? '…' : '')
            . trim($excerpt)
            . (mb_strlen($content) > ($start + $length) ? '…' : '');
    }
}