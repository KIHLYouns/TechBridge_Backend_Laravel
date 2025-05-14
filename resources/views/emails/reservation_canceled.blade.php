<h2>Your Reservation Has Been Refunded</h2>
<p>Hello {{ $reservation->client->username ?? 'Client' }},</p>

<p>Your reservation has been <strong>canceled</strong> and your payment has been refunded.</p>

<p>Reservation details:</p>
<ul>
    <li>Client: {{ $reservation->client->username ?? 'N/A' }}</li>
    <li>Reservation ID: {{ $reservation->id }}</li>
    <li>Total Refund: ${{ number_format($reservation->payment->amount, 2) }}</li>
</ul>

<p>Regards,<br>Reservation System</p>
