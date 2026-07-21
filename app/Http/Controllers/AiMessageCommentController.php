<?php

namespace App\Http\Controllers;

use App\Models\AiMessage;
use App\Models\AiMessageComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AiMessageCommentController extends Controller
{
    public function store(
        Request $request,
        AiMessage $message
    ): RedirectResponse {
        $this->authorizeMessage($request, $message);

        $validated = $request->validate([
            'content' => [
                'required',
                'string',
                'max:500',
            ],
        ], [
            'content.required' => 'O comentário é obrigatório.',
            'content.max' => 'O comentário não pode ter mais de 500 caracteres.',
        ]);

        $message->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        return back()->with(
            'success',
            'Comentário adicionado com sucesso.'
        );
    }

    public function update(
        Request $request,
        AiMessageComment $comment
    ): RedirectResponse {
        $this->authorizeComment($request, $comment);

        $validated = $request->validate([
            'content' => [
                'required',
                'string',
                'max:500',
            ],
        ], [
            'content.required' => 'O comentário é obrigatório.',
            'content.max' => 'O comentário não pode ter mais de 500 caracteres.',
        ]);

        $comment->update([
            'content' => $validated['content'],
        ]);

        return back()->with(
            'success',
            'Comentário atualizado com sucesso.'
        );
    }

    public function destroy(
        Request $request,
        AiMessageComment $comment
    ): RedirectResponse {
        $this->authorizeComment($request, $comment);

        $comment->delete();

        return back()->with(
            'success',
            'Comentário eliminado com sucesso.'
        );
    }

    private function authorizeMessage(
        Request $request,
        AiMessage $message
    ): void {
        $message->loadMissing('conversation');

        abort_unless(
            $message->conversation
            && $message->conversation->user_id === $request->user()->id,
            403
        );
    }

    private function authorizeComment(
        Request $request,
        AiMessageComment $comment
    ): void {
        $comment->loadMissing('message.conversation');

        abort_unless(
            $comment->user_id === $request->user()->id,
            403
        );

        abort_unless(
            $comment->message
            && $comment->message->conversation
            && $comment->message->conversation->user_id === $request->user()->id,
            403
        );
    }
}