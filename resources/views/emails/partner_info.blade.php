
<p>Hello,

Your reservation has been confirmed. Here are the details of the partner:<br>

- <strong>Username:</strong> {{ $partner->username }}<br>
- <strong>Name:</strong> {{ $partner->firstname }} {{ $partner->lastname }}<br>
- <strong>Email:</strong> {{ $partner->email }}<br>
- <strong>Phone:</strong>  {{ $partner->phone_number ?? 'N/A' }}<br>
- <strong>Address:</strong>  {{ $partner->address}}<br>

Thank you for choosing us.

Regards,  
{{ config('app.name') }}<p>

