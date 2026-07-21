<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class AiChatExportController extends Controller
{
    public function markdown(Request $request, AiConversation $conversation): Response
    {
        $this->authorizeConversation($request, $conversation);

        $conversation->load([
            'messages' => fn ($query) => $query->orderBy('created_at'),
        ]);

        if ($conversation->messages->isEmpty()) {
            abort(422, 'Não é possível exportar uma conversa sem mensagens.');
        }

        $content = $this->buildMarkdown($conversation);

        $date = optional($conversation->created_at)->format('Y-m-d')
            ?? now()->format('Y-m-d');

        $fileName = Str::slug($conversation->title ?: 'conversa-ai')
            . '-'
            . $date
            . '.md';

        return response($content, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function pdf(Request $request, AiConversation $conversation)
    {
        $this->authorizeConversation($request, $conversation);

        $conversation->load([
            'messages' => fn ($query) => $query->orderBy('created_at'),
        ]);
        
        if ($conversation->messages->isEmpty()) {
            abort(422, 'Não é possível exportar uma conversa sem mensagens.');
        }

        $date = optional($conversation->created_at)->format('Y-m-d')
            ?? now()->format('Y-m-d');

        $fileName = Str::slug($conversation->title ?: 'conversa-ai')
            . '-'
            . $date
            . '.pdf';

        $pdf = Pdf::loadView('exports.ai-chat-conversation', [
            'conversation' => $conversation,
        ])->setPaper('a4');

        return $pdf->download($fileName);
    }

    private function authorizeConversation(Request $request, AiConversation $conversation): void
    {
        abort_unless(
            $conversation->user_id === $request->user()->id,
            403
        );
    }

    private function buildMarkdown(AiConversation $conversation): string
    {
        $lines = [];

        $lines[] = '# ' . ($conversation->title ?: 'Conversa sem título');
        $lines[] = '';
        $lines[] = '**Data de criação:** ' . optional($conversation->created_at)->format('d/m/Y H:i');
        $lines[] = '**Modelo principal:** ' . ($conversation->model_id ?: 'Sem modelo');
        $lines[] = '**Temperature:** ' . $conversation->temperature;
        $lines[] = '**Max tokens:** ' . $conversation->max_tokens;
        $lines[] = '';
        $lines[] = '---';
        $lines[] = '';

        foreach ($conversation->messages as $message) {
            $author = $message->role === 'user'
                ? 'Utilizador'
                : 'IA';

            $lines[] = '## ' . $author;
            $lines[] = '';
            $lines[] = '**Data/Hora:** ' . optional($message->created_at)->format('d/m/Y H:i');
            $lines[] = '**Modelo:** ' . ($message->model_id ?: $conversation->model_id ?: 'Sem modelo');
            $lines[] = '';
            $lines[] = $message->content ?: '';
            $lines[] = '';
            $lines[] = '---';
            $lines[] = '';
        }

        return implode("\n", $lines);
    }
}