@component('mail::message')

# ⏰ Lembrete de Devolução

Olá {{ $request->user->name }},

Este é um lembrete de que o livro:

📚 **{{ $request->book->name }}**

deve ser devolvido amanhã ({{ \Carbon\Carbon::parse($request->expected_return_date)->format('d/m/Y') }}).

Por favor, não se esqueça da devolução.

Obrigado!

@endcomponent