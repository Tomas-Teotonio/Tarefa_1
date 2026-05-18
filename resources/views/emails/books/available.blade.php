<x-mail::message>
# Livro disponível

Olá {{ $alert->user->name }},

O livro que tinhas marcado para aviso já se encontra disponível para requisição.

<x-mail::panel>
**Livro:** {{ $alert->book->name }}

**ISBN:** {{ $alert->book->isbn }}
</x-mail::panel>

@php
    $cover = $alert->book->cover_image;
    $localCoverPath = null;

    if ($cover && !str_starts_with($cover, 'http')) {
        $localCoverPath = storage_path('app/public/' . $cover);
    }
@endphp

@if ($cover)
**Capa do livro**

@if ($localCoverPath && file_exists($localCoverPath))
<img src="{{ $message->embed($localCoverPath) }}" width="160" style="border-radius: 8px; margin-top: 10px;">
@elseif (str_starts_with($cover, 'http'))
<img src="{{ $cover }}" width="160" style="border-radius: 8px; margin-top: 10px;">
@endif

@endif

<x-mail::button :url="route('books.show', $alert->book)">
Ver livro
</x-mail::button>

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>