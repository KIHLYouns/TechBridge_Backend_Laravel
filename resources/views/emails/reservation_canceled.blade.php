<h2>Reservation Canceled</h2>
<p>Hello {{ $reservation->partner->name ?? 'Partner' }},</p>

<p>The reservation made by {{ $reservation->client->username ?? 'a client' }} has been <strong>canceled</strong>.</p>

<p>Reservation details:</p>
<ul>
    <li>ID: {{ $reservation->id }}</li>
    <li>Client: {{ $reservation->client->username ?? 'N/A' }}</li>
    <li>Date: {{ $reservation->date ?? 'N/A' }}</li>
    <!-- Add other relevant details -->
</ul>

<p>Regards,<br>Reservation System</p>
