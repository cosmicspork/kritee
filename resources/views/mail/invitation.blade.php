<x-mail::message>
# You've been invited

@if ($inviterName)
{{ $inviterName }} has invited you to join **{{ config('app.name') }}** as a **{{ $role }}**.
@else
You've been invited to join **{{ config('app.name') }}** as a **{{ $role }}**.
@endif

Click below to set up your account. This invitation expires in 7 days.

<x-mail::button :url="$acceptUrl">
Accept invitation
</x-mail::button>

If you weren't expecting this invitation, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
