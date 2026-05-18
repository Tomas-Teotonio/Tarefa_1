<x-mail::message>
# Nova review pendente

Foi submetida uma nova review e está a aguardar moderação.

<x-mail::panel>
**Cidadão:** {{ $review->user->name }}

**Email:** {{ $review->user->email }}

**Livro:** {{ $review->book->name }}

**Estado:** Suspensa
</x-mail::panel>

**Conteúdo da review**

{{ $review->content }}

<x-mail::button :url="route('admin.reviews.show', $review)">
Ver detalhe da review
</x-mail::button>

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>