<x-mail::message>

<div style="text-align: center; margin-bottom: 20px;">
    <img src="{{ $message->embed(public_path('images/full-logo.png')) }}" alt="Logo" style="max-height: 80px;">
</div>

# New Booking Request

Hello {{ $consultantName }},

A client has requested to book a session with you.
Here are the booking details:

<x-mail::panel>
    <p>
        Client Name: {{ $username }}<br>
        Client Email: {{ $clientEmail }}<br>
        Preferred Date: {{ $booking->consultant_date }}<br>
        Preferred Time: {{ $booking->start_time.' - '.$booking->end_time }}<br>
        Additional Notes: {{ $booking->note ?? 'N/A' }}
    </p>

</x-mail::panel>

Please log in to your dashboard to confirm or reschedule the session.

<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn btn-primary">
    <tbody>
      <tr>
        <td align="center">
          <a href="{{ $dashboardUrl }}" target="_blank"
             style="background-color: #2563eb; color: #ffffff; padding: 12px 24px;
                    border-radius: 6px; display: inline-block; font-size: 16px;
                    text-decoration: none; font-weight: bold;">
            View Booking in Dashboard
          </a>
        </td>
      </tr>
    </tbody>
</table>
<br>
<p> Thanks, </p>
<p>{{ config('app.name') }}</p>
</x-mail::message>
