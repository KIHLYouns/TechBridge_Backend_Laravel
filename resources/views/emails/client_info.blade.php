@component('mail::message')
# Client Information

Hello,

Here are the details of the client for your confirmed reservation:

- **Name:** {{ $client->name }}
- **Email:** {{ $client->email }}
- **Phone:** {{ $client->phone ?? 'N/A' }}

Please proceed accordingly.

Thanks,  
{{ config('app.name') }}
@endcomponent
