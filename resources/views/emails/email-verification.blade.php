<x-mail::message>

<div style="text-align: center; margin-bottom: 20px;">
    <img src="{{ $message->embed(public_path('images/logo-3.png')) }}" alt="Logo" style="max-height: 80px;">
</div>

# Email Verification

Thank you for signing up!

Please use the verification code below to verify your email address:

## {{ $code }}

If you did not initiate this request, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>