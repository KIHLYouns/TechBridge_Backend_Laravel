<p>Hello,</p>

<p>Here are the details of the client for your confirmed reservation:</p>

<ul>
    <li><strong>Username:</strong> {{ $client->username }}</li>
    <li><strong>Name:</strong> {{ $client->firstname }} {{ $client->lastname }}</li>
    <li><strong>Email:</strong> {{ $client->email }}</li>
    <li><strong>Phone:</strong> {{ $client->phone_number ?? 'N/A' }}</li>
    <li><strong>Address:</strong> {{ $client->address }}</li>
</ul>

<p>We have successfully sent you the amount of <strong>${{ number_format($reservation->total_cost, 2) }}</strong> for this reservation.</p>

<p>Please proceed accordingly.</p>

<p>Thanks,  
{{ config('app.name') }}</p>
