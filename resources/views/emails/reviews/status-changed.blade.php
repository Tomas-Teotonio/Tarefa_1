<x-mail::message>
# Estado da tua review

Olá {{ $review->user->name }},

A tua review ao livro **{{ $review->book->name }}** foi analisada.

<x-mail::panel>
**Livro:** {{ $review->book->name }}

**Estado:** {{ $review->status === 'active' ? 'Ativa' : 'Recusada' }}
</x-mail::panel>

@if ($review->status === 'refused')
**Justificação da recusa**

{{ $review->refusal_reason }}
@endif

<x-mail::button :url="route('books.show', $review->book)">
Ver livro
</x-mail::button>

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>