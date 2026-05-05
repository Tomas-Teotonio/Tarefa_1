@component('mail::message')
# 📚 Nova Requisição de Livro

Olá {{ $request->user->name }},

A tua requisição foi criada com sucesso.

---

### 📖 Livro
**{{ $request->book->name }}**

---

### 📅 Datas
- Data da requisição: {{ \Carbon\Carbon::parse($request->request_date)->format('d/m/Y') }}
- Data prevista de entrega: {{ \Carbon\Carbon::parse($request->expected_return_date)->format('d/m/Y') }}

---

### 🔢 Número da requisição
**{{ $request->number }}**

---

@if($request->book->cover_image)
### 🖼️ Capa do livro
<img src="{{ asset('storage/' . $request->book->cover_image) }}" width="150">
@endif

---

Obrigado,<br>
{{ config('app.name') }}
@endcomponentr