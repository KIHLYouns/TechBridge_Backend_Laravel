<!DOCTYPE html>
<html>
<head>
    <title>Réservation refusée</title>
</head>
<body>
    <h1>Votre réservation a été refusée</h1>
    
    <p>Bonjour {{ $reservation->client->username }},</p>
    
    <p>Nous vous informons que votre réservation a été refusée et que votre paiement a été remboursé.</p>

    <p>Voici les détails de votre remboursement :</p>
    <ul>
        <li>Client: {{ $reservation->client->username ?? 'N/A' }}</li>
        <li>Reservation ID: {{ $reservation->id }}</li>
        <li>Total Refund: ${{ number_format($reservation->payment->amount, 2) }}</li> <!-- Montant du remboursement -->
    </ul>

    <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>

    <p>Cordialement,<br>
    L'équipe de votre application</p>
</body>
</html>
