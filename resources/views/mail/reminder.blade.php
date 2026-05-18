<x-mail::message>
# {{ $reminder->type->label() }}

Bonjour **{{ $reminder->customer->first_name }} {{ $reminder->customer->last_name }}**,

Nous vous rappelons que votre véhicule **{{ $reminder->vehicle->make }} {{ $reminder->vehicle->model }}** ({{ $reminder->vehicle->license_plate }}) est prévu pour une **{{ $reminder->type->label() }}** le **{{ $reminder->scheduled_for->format('d/m/Y') }}**.

@if ($reminder->trigger_km)
Ce rappel est déclenché à l'approche de **{{ number_format($reminder->trigger_km, 0, ',', ' ') }} km**.
@endif

@if ($reminder->notes)
*Note :* {{ $reminder->notes }}
@endif

Pour prendre rendez-vous ou pour toute question, n'hésitez pas à nous contacter.

<x-mail::button :url="route('appointments.create')">
Prendre rendez-vous
</x-mail::button>

Cordialement,<br>
{{ config('app.name') }}
</x-mail::message>
