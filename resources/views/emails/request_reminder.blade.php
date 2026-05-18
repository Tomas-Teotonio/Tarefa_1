<x-mail::message>
# Lembrete de devolução

Olá {{ $request->user->name }},

Este é um lembrete de que o livro abaixo deve ser devolvido amanhã.

<x-mail::panel>
**Livro:** {{ $request->book->name }}

**Número da requisição:** {{ $request->number }}

**Data prevista de entrega:** {{ \Carbon\Carbon::parse($request->expected_return_date)->format('d/m/Y') }}
</x-mail::panel>

<x-mail::button :url="route('requests.show', $request)">
Ver detalhe da requisição
</x-mail::button>

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>