<x-mail::message>

<div style="text-align: center; margin-bottom: 20px;">
    <img src="{{ $message->embed(public_path('images/full-logo.png')) }}" alt="Logo" style="max-height: 80px;">
</div>

# Password Reset Request

We received a request to reset your password.

Please use the code below to reset your password:

# {{ $code }}

If you did not request a password reset, please ignore this email.
For security, this code will expire in **15 minutes**.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
