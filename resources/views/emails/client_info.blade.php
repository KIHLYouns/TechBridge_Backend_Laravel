
<p>Hello,

Here are the details of the client for your confirmed reservation:

- <strong>Username:</strong> {{ $client->username }}<br>
- <strong>Name:</strong> {{ $client->firstname }} {{ $client->lastname }}<br>
- <strong>Email:</strong> {{ $client->email }}<br>
- <strong>Phone:</strong>  {{ $client->phone_number ?? 'N/A' }}<br>
- <strong>Address:</strong>  {{ $client->address}}<br>

Please proceed accordingly.

Thanks,  
{{ config('app.name') }}
</p>

