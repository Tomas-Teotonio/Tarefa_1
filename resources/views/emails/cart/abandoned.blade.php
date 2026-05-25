<x-mail::message>
# Ainda tens livros no carrinho

Olá {{ $user->name }},

Reparámos que adicionaste livros ao carrinho, mas ainda não terminaste a encomenda.

Precisas de ajuda para concluir a compra?

<x-mail::panel>
@foreach ($items as $item)
**{{ $item->book->name }}**

Quantidade: {{ $item->quantity }}

Preço unitário: {{ number_format((float) $item->book->price, 2, ',', '.') }} €

Subtotal: {{ number_format((float) $item->book->price * $item->quantity, 2, ',', '.') }} €

@if (!$loop->last)
---
@endif
@endforeach

**Total:** {{ number_format($total, 2, ',', '.') }} €
</x-mail::panel>

<x-mail::button :url="route('cart.index')">
Voltar ao carrinho
</x-mail::button>

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>