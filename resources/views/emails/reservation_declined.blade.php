
<!DOCTYPE html>
<html>
<head>
    <title>Réservation refusée</title>
</head>
<body>
    <h1>Votre réservation a été refusée</h1>
    
    <p>Bonjour {{ $reservation->client->username }},</p>
    
    <p>Nous vous informons que votre réservation a été refusée .</p>
    
    
    <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>
    
    <p>Cordialement,<br>
    L'équipe de votre application</p>
</body>
</html>