<x-mail::message>
# Convite para equipa

Foste convidado para te juntares à equipa **{{ $invitation->team->name }}**.

@if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::registration()))
Se ainda não tens conta, podes criar uma através do botão abaixo. Depois de criares conta, volta a este email e aceita o convite.

<x-mail::button :url="route('register')">
Criar conta
</x-mail::button>

Se já tens conta, podes aceitar o convite diretamente:

@else
Podes aceitar o convite através do botão abaixo:
@endif

<x-mail::button :url="$acceptUrl">
Aceitar convite
</x-mail::button>

Se não esperavas este convite, podes ignorar este email.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>