<?php
session_start();
$db = new SQLite3('../database/crunchtime.db');
$apiKey = 'ad3586245e96a667f42a02c1b8708569';

// Fonction pour générer les étoiles en PHP
function generateStars($rating) {
    // Convertir la note sur 10 en note sur 5
    $rating = $rating / 2;

    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;

    $stars = str_repeat('★', $fullStars); // Étoiles pleines
    if ($halfStar) {
        $stars .= '★'; // Utiliser une étoile pleine pour les demi-étoiles aussi
    }
    $stars .= str_repeat('☆', $emptyStars); // Étoiles vides

    return $stars;
}
$mediaDetails = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("SELECT media_id, media_type, added_at FROM watchlist WHERE user_id = :user_id ORDER BY added_at DESC LIMIT 4");    $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();

    $mediaList = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $mediaList[] = [
            'id' => $row['media_id'],
            'type' => $row['media_type'],
            'added_at' => $row['added_at']
        ];
    }

    foreach ($mediaList as $media) {
        $media_id = $media['id'];
        $media_type = $media['type'];
    
        $apiUrl = "https://api.themoviedb.org/3/{$media_type}/{$media_id}?api_key={$apiKey}&language=fr-FR";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
    
        $mediaData = json_decode($response, true);
    
        // Récupérer le réalisateur depuis la section 'credits'
        $director = 'Inconnu';
        $creditsUrl = "https://api.themoviedb.org/3/{$media_type}/{$media_id}/credits?api_key={$apiKey}&language=fr-FR";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $creditsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $creditsResponse = curl_exec($ch);
        curl_close($ch);
    
        $creditsData = json_decode($creditsResponse, true);
        if (isset($creditsData['crew'])) {
            foreach ($creditsData['crew'] as $person) {
                if ($person['job'] === 'Director') {
                    $director = $person['name']; // Nom du réalisateur
                    break; // Sortir dès qu'on trouve le réalisateur
                }
            }
        }
    
        // Ajouter ces informations au tableau des films
        $mediaDetails[] = [
            'id' => $mediaData['id'],
            'title' => $mediaData['title'] ?? $mediaData['name'],
            'poster_path' => $mediaData['poster_path'] ?? '',
            'release_date' => $mediaData['release_date'] ?? $mediaData['first_air_date'],
            'vote_average' => $mediaData['vote_average'],
            'director' => $director, // Assurez-vous que le réalisateur est ajouté correctement
        ];
    }
}

if (empty($mediaDetails)) {
    echo '<p class="erreurWatchlist">Vous n\'avez actuellement aucun film ou série dans votre watchlist.</p>';
} else {
    foreach ($mediaDetails as $media) {
        ?>
        <div id="media-container">
            <div class="movie-poster" id="media-poster-container">
                <a href="details.php?id=<?= $media['id'] ?>">
                    <?php if (!empty($media['poster_path'])): ?>
                        <img src="https://image.tmdb.org/t/p/w500<?= $media['poster_path'] ?>" alt="<?= htmlspecialchars($media['title'] ?? $media['name']) ?>">
                    <?php else: ?>
                        <img src="../assets/images/placeholder.png" alt="<?= htmlspecialchars($media['title'] ?? $media['name']) ?>" class="placeholder-poster">
                    <?php endif; ?>
                </a>
            </div>
            <div id="media-title-container">
                <h3><?= htmlspecialchars($media['title'] ?? $media['name']) ?></h3>
                <p class="release-year"><?= date("Y", strtotime($media['release_date'] ?? $media['first_air_date'])) ?></p> <!-- Année -->
                <p class="director"><?= $media['director'] ?? 'Inconnu' ?></p> <!-- Réalisateur -->           
                <p class="star-rating"><?= generateStars($media['vote_average']) ?></p> <!-- Remplacer la note par des étoiles -->            

            </div>
        </div>
        <?php
    }
}
