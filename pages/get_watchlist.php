<?php
session_start(); // Démarre une session pour accéder aux variables de session
$db = new SQLite3('../database/crunchtime.db'); // Connexion à la base de données SQLite
$apiKey = 'ad3586245e96a667f42a02c1b8708569'; // Clé API pour accéder à l'API de The Movie Database (TMDb)

// Fonction pour générer une représentation visuelle des notes sous forme d'étoiles
function generateStars($rating)
{
    // Convertit la note sur 10 en une note sur 5
    $rating = $rating / 2;

    // Calcule le nombre d'étoiles pleines, demi-étoiles et étoiles vides
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;

    // Génère les étoiles pleines
    $stars = str_repeat('★', $fullStars);
    if ($halfStar) {
        $stars .= '★'; // Utilise une étoile pleine pour représenter une demi-étoile
    }
    // Génère les étoiles vides
    $stars .= str_repeat('☆', $emptyStars);

    return $stars; // Retourne la chaîne d'étoiles
}

$mediaDetails = []; // Tableau pour stocker les détails des médias
if (isset($_SESSION['user_id'])) { // Vérifie si l'utilisateur est connecté
    // Prépare une requête pour récupérer les médias de la watchlist de l'utilisateur
    $stmt = $db->prepare("SELECT media_id, media_type, added_at FROM watchlist WHERE user_id = :user_id ORDER BY added_at DESC LIMIT 4");
    $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER); // Lie l'ID utilisateur à la requête
    $result = $stmt->execute(); // Exécute la requête

    $mediaList = []; // Tableau pour stocker les médias récupérés
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) { // Parcourt les résultats de la requête
        $mediaList[] = [
            'id' => $row['media_id'], // ID du média
            'type' => $row['media_type'], // Type du média (film ou série)
            'added_at' => $row['added_at'] // Date d'ajout à la watchlist
        ];
    }

    // Parcourt chaque média pour récupérer ses détails via l'API TMDb
    foreach ($mediaList as $media) {
        $media_id = $media['id']; // ID du média
        $media_type = $media['type']; // Type du média

        // URL pour récupérer les détails du média
        $apiUrl = "https://api.themoviedb.org/3/{$media_type}/{$media_id}?api_key={$apiKey}&language=fr-FR";
        $ch = curl_init(); // Initialise une session cURL
        curl_setopt($ch, CURLOPT_URL, $apiUrl); // Définit l'URL de la requête
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Retourne la réponse sous forme de chaîne
        $response = curl_exec($ch); // Exécute la requête
        curl_close($ch); // Ferme la session cURL

        $mediaData = json_decode($response, true); // Décode la réponse JSON en tableau PHP

        // Récupère le réalisateur du média via une autre requête API
        $director = 'Inconnu'; // Valeur par défaut si le réalisateur n'est pas trouvé
        $creditsUrl = "https://api.themoviedb.org/3/{$media_type}/{$media_id}/credits?api_key={$apiKey}&language=fr-FR";
        $ch = curl_init(); // Nouvelle session cURL pour les crédits
        curl_setopt($ch, CURLOPT_URL, $creditsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $creditsResponse = curl_exec($ch);
        curl_close($ch);

        $creditsData = json_decode($creditsResponse, true); // Décode les crédits
        if (isset($creditsData['crew'])) { // Vérifie si l'équipe technique est disponible
            foreach ($creditsData['crew'] as $person) { // Parcourt l'équipe technique
                if ($person['job'] === 'Director') { // Cherche le réalisateur
                    $director = $person['name']; // Récupère le nom du réalisateur
                    break; // Arrête la recherche dès qu'un réalisateur est trouvé
                }
            }
        }

        // Ajoute les détails du média au tableau
        $mediaDetails[] = [
            'id' => $mediaData['id'], // ID du média
            'title' => $mediaData['title'] ?? $mediaData['name'], // Titre ou nom
            'poster_path' => $mediaData['poster_path'] ?? '', // Chemin de l'affiche
            'release_date' => $mediaData['release_date'] ?? $mediaData['first_air_date'], // Date de sortie
            'vote_average' => $mediaData['vote_average'], // Note moyenne
            'director' => $director, // Nom du réalisateur
        ];
    }
}

// Affiche un message si la watchlist est vide
if (empty($mediaDetails)) {
    echo '<p class="erreurWatchlist" role="alert">Vous n\'avez actuellement aucun film ou série dans votre watchlist.</p>';
} else {
    // Parcourt les médias pour les afficher
    foreach ($mediaDetails as $media) {
        ?>
        <div id="media-container" role="region" aria-label="Média">
            <div class="movie-poster" id="media-poster-container" role="img" aria-label="Affiche du média">
                <a href="details.php?id=<?= $media['id'] ?>" aria-label="Voir les détails de <?= htmlspecialchars($media['title'] ?? $media['name']) ?>">
                    <?php if (!empty($media['poster_path'])): ?>
                        <!-- Affiche l'affiche du média si disponible -->
                        <img src="https://image.tmdb.org/t/p/w500<?= $media['poster_path'] ?>"
                            alt="Affiche de <?= htmlspecialchars($media['title'] ?? $media['name']) ?>">
                    <?php else: ?>
                        <!-- Affiche une image de remplacement si aucune affiche n'est disponible -->
                        <img src="../assets/images/placeholder.png" 
                            alt="Image de remplacement pour <?= htmlspecialchars($media['title'] ?? $media['name']) ?>"
                            class="placeholder-poster">
                    <?php endif; ?>
                </a>
            </div>
            <div id="media-title-container" role="region" aria-label="Informations sur le média">
                <h3><?= htmlspecialchars($media['title'] ?? $media['name']) ?></h3> <!-- Titre du média -->
                <p class="release-year" aria-label="Année de sortie : <?= date("Y", strtotime($media['release_date'] ?? $media['first_air_date'])) ?>">
                    <?= date("Y", strtotime($media['release_date'] ?? $media['first_air_date'])) ?>
                </p>
                <p class="director" aria-label="Réalisateur : <?= $media['director'] ?? 'Inconnu' ?>">
                    <?= $media['director'] ?? 'Inconnu' ?>
                </p>
                <p class="star-rating" aria-label="Note moyenne : <?= $media['vote_average'] ?>/10">
                    <?= generateStars($media['vote_average']) ?>
                </p>
            </div>
        </div>
        <?php
    }
}
?>
