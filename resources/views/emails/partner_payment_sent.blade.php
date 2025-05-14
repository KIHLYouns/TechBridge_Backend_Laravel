<!DOCTYPE html>
<html>
<head>
    <title>Payment Sent</title>
</head>
<body>
    <p>Hello {{ $reservation->partner->name }},</p>

    <p>We are pleased to inform you that the payment for the reservation has been successfully processed.</p>

    <p>
        <strong>Total amount sent:</strong> ${{ number_format($reservation->payment->amount, 2) }}<br>
        <strong>Commission taken by the app:</strong> ${{ number_format($reservation->payment->commission_fee, 2) }}
    </p>

    <p>The amount sent is calculated after deducting the commission charged by the platform.</p>

    <p>Thank you for using our platform.</p>

    <p>Best regards,<br>
    {{ config('app.name') }} Team</p>
</body>
</html>
