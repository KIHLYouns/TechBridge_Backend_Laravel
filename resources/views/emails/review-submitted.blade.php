<h2>Nouvelle évaluation reçue</h2>
<p>Vous avez reçu une nouvelle évaluation de {{ $review->reviewer->firstname }} :</p>
<p><strong>Note :</strong> {{ $review->rating }}/5</p>
<p><strong>Commentaire :</strong> {{ $review->comment }}</p>
<p><strong>Type :</strong> {{ $review->type }}</p>
<p>Soumise le {{ $review->created_at->format('d/m/Y à H:i') }}</p>
