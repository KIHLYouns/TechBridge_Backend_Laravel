<h2>Reservation Canceled</h2>
<p>Hello ,</p>

<p>The reservation made by {{ $reservation->client->username ?? 'a client' }} has been <strong>canceled</strong>.</p>

<p>Reservation details:</p>
<ul>
    <li>Client: {{ $reservation->client->username ?? 'N/A' }}</li>

</ul>

<p>Regards,<br>Reservation System</p>
