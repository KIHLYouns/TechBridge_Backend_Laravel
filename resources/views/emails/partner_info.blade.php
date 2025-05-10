@component('mail::message')
# Partner Information

Hello,

Your reservation has been confirmed. Here are the details of the partner:

- **Name:** {{ $partner->name }}
- **Email:** {{ $partner->email }}
- **Phone:** {{ $partner->phone ?? 'N/A' }}

Thank you for choosing us.

Regards,  
{{ config('app.name') }}
@endcomponent
