<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\AiConversationShare;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AiChatShareController extends Controller
{
    public function store(
        Request $request,
        AiConversation $conversation
    ): RedirectResponse {
        $this->authorizeConversation(
            $request,
            $conversation
        );

        $validated = $request->validate([
            'visibility' => [
                'required',
                'string',
                'in:public,restricted',
            ],
        ], [
            'visibility.required' => 'Seleciona o tipo de partilha.',
            'visibility.in' => 'O tipo de partilha selecionado é inválido.',
        ]);

        $revokedLinks = 0;

        DB::transaction(function () use (
            $conversation,
            $validated,
            &$revokedLinks
        ) {
            /*
            * Invalida todos os links ativos anteriores
            * do mesmo tipo.
            */
            $revokedLinks = AiConversationShare::query()
                ->where('ai_conversation_id', $conversation->id)
                ->where('visibility', $validated['visibility'])
                ->whereNull('revoked_at')
                ->update([
                    'revoked_at' => now(),
                ]);

            /*
            * Cria sempre um token novo.
            */
            AiConversationShare::create([
                'ai_conversation_id' => $conversation->id,
                'visibility' => $validated['visibility'],
                'token' => $this->generateToken(),
            ]);
        });

        $message = $revokedLinks > 0
            ? 'Novo link criado. O link anterior foi invalidado.'
            : 'Link de partilha criado com sucesso.';

        return back()->with('success', $message);
    }

    public function destroy(Request $request, AiConversationShare $share): RedirectResponse
    {
        $share->load('conversation');

        $this->authorizeConversation($request, $share->conversation);

        $share->update([
            'revoked_at' => now(),
        ]);

        return back()->with('success', 'Link de partilha revogado com sucesso.');
    }

    public function show(string $token)
    {
        $share = AiConversationShare::query()
            ->with([
                'conversation.messages' => function ($query) {
                    $query->orderBy('created_at');
                },
            ])
            ->where('token', $token)
            ->whereNull('revoked_at')
            ->firstOrFail();

        if ($share->isRestricted() && !Auth::check()) {
            return redirect()->route('login');
        }

        $conversation = $share->conversation;

        return Inertia::render('AiChat/Shared', [
            'share' => [
                'visibility' => $share->visibility,
                'created_at' => $share->created_at,
            ],
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'model_id' => $conversation->model_id,
                'temperature' => $conversation->temperature,
                'max_tokens' => $conversation->max_tokens,
                'created_at' => $conversation->created_at,
                'messages' => $conversation->messages->map(fn ($message) => [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'model_id' => $message->model_id,
                    'created_at' => $message->created_at,
                ]),
            ],
        ]);
    }

    private function authorizeConversation(Request $request, AiConversation $conversation): void
    {
        abort_unless(
            $conversation->user_id === $request->user()->id,
            403
        );
    }

    private function generateToken(): string
    {
        do {
            $token = Str::random(64);
        } while (AiConversationShare::where('token', $token)->exists());

        return $token;
    }
}