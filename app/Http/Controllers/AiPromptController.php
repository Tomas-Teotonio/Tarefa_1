<?php

namespace App\Http\Controllers;

use App\Models\AiPrompt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AiPromptController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'content' => ['required', 'string', 'max:2000'],
        ], [
            'name.required' => 'O nome do prompt é obrigatório.',
            'name.max' => 'O nome do prompt não pode ter mais de 80 caracteres.',
            'content.required' => 'O texto do prompt é obrigatório.',
            'content.max' => 'O texto do prompt não pode ter mais de 2000 caracteres.',
        ]);

        $request->user()->aiPrompts()->create($validated);

        return back()->with('success', 'Prompt criado com sucesso.');
    }

    public function update(Request $request, AiPrompt $prompt): RedirectResponse
    {
        $this->authorizePrompt($request, $prompt);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'content' => ['required', 'string', 'max:2000'],
        ], [
            'name.required' => 'O nome do prompt é obrigatório.',
            'name.max' => 'O nome do prompt não pode ter mais de 80 caracteres.',
            'content.required' => 'O texto do prompt é obrigatório.',
            'content.max' => 'O texto do prompt não pode ter mais de 2000 caracteres.',
        ]);

        $prompt->update($validated);

        return back()->with('success', 'Prompt atualizado com sucesso.');
    }

    public function destroy(Request $request, AiPrompt $prompt): RedirectResponse
    {
        $this->authorizePrompt($request, $prompt);

        $prompt->delete();

        return back()->with('success', 'Prompt eliminado com sucesso.');
    }

    private function authorizePrompt(Request $request, AiPrompt $prompt): void
    {
        abort_unless(
            $prompt->user_id === $request->user()->id,
            403
        );
    }
}