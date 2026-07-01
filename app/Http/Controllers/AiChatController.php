<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Services\OpenRouterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AiChatController extends Controller
{
    public function index(OpenRouterService $openRouter)
    {
        $user = Auth::user();

        $conversations = AiConversation::query()
            ->where('user_id', $user->id)
            ->latest()
            ->get([
                'id',
                'title',
                'model_id',
                'temperature',
                'max_tokens',
                'created_at',
                'updated_at',
            ]);

        $modelsResult = $openRouter->models();

        return Inertia::render('AiChat/Index', [
            'activeConversation' => null,
            'conversations' => $conversations,
            'models' => $modelsResult['models'],
            'openRouterConfigured' => $openRouter->isConfigured(),
            'modelsError' => $modelsResult['error'],
        ]);
    }

    public function show(AiConversation $conversation, OpenRouterService $openRouter)
    {
        $this->authorizeConversation($conversation);

        $conversation->load([
            'messages' => fn ($query) => $query->oldest(),
        ]);

        $conversations = AiConversation::query()
            ->where('user_id', Auth::id())
            ->latest()
            ->get([
                'id',
                'title',
                'model_id',
                'temperature',
                'max_tokens',
                'created_at',
                'updated_at',
            ]);

        $modelsResult = $openRouter->models();

        return Inertia::render('AiChat/Index', [
            'activeConversation' => $conversation,
            'conversations' => $conversations,
            'models' => $modelsResult['models'],
            'openRouterConfigured' => $openRouter->isConfigured(),
            'modelsError' => $modelsResult['error'],
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

    public function stream(Request $request, OpenRouterService $openRouter)
    {
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
        ]);

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
                'title' => $this->generateTitle($data['message']),
                'model_id' => $data['model_id'],
                'temperature' => $data['temperature'],
                'max_tokens' => $data['max_tokens'],
            ]);
        }

        $conversation->messages()->create([
            'role' => 'user',
            'content' => $data['message'],
            'model_id' => $data['model_id'],
        ]);

        $history = $conversation->messages()
            ->oldest()
            ->get()
            ->map(fn ($message) => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->toArray();

        return response()->stream(function () use ($conversation, $history, $data, $openRouter) {
            $this->prepareStream();

            $this->sendSse('conversation', [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'url' => route('ai-chat.show', $conversation),
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
}