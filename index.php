<?php
session_start();

// Connexion à SQLite
$db = new SQLite3('crunchtime.db');

$user = null; // Initialiser la variable utilisateur

// Vérifier si l'utilisateur est connecté
if (isset($_SESSION['user_id'])) {
    // Préparer et exécuter la requête pour récupérer les infos de l'utilisateur
    $stmt = $db->prepare("SELECT id, username, firstname, lastname, email, date FROM users WHERE id = :id");
    $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $user = $result->fetchArray(SQLITE3_ASSOC);
}

// Clé API de TMDB (remplace-la par la tienne)
$apiKey = 'ad3586245e96a667f42a02c1b8708569';

// URL de l'API pour les films tendances sur la semaine
$apiUrl = "https://api.themoviedb.org/3/trending/movie/week?api_key=$apiKey&language=fr-FR";

// Initialiser cURL pour récupérer les films tendances
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// Exécuter la requête et obtenir la réponse JSON
$response = curl_exec($ch);
curl_close($ch);

// Décoder la réponse JSON en tableau associatif PHP
$movies = json_decode($response, true);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <title>Accueil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body id="homePage">
    <?php include 'pages/header.php'; ?>

    <main> 
    <div class="filmsRecents">
        <div class="catFilmsRecents">
        <h3>Films sorti récemments</h3>
        <div class="buttonNav">
<img src="assets/images/icon/arrowLeft.svg" alt="" class=" prev">
<img src="assets/images/icon/arrowRight.svg" class="next" alt="">
        </div>
        </div>
            <div class="carousel-container">

                <div class="trending-movies">
                    <?php
                    // Vérifier si la réponse contient des films
                    if (isset($movies['results'])) {
                        // Parcourir chaque film dans les résultats
                        foreach ($movies['results'] as $movie) {
                            $movieId = $movie['id']; // ID du film
                            $movieTitle = htmlspecialchars($movie['title']); // Titre
                            $moviePoster = "https://image.tmdb.org/t/p/w500" . $movie['poster_path']; // Affiche
                            $releaseYear = date('Y', strtotime($movie['release_date'])); // Année de sortie
                            $rating = htmlspecialchars($movie['vote_average']); // Note

                            // Récupération des détails du film (réalisateur, durée, catégories)
                            $detailsUrl = "https://api.themoviedb.org/3/movie/$movieId?api_key=$apiKey&language=fr-FR&append_to_response=credits";
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $detailsUrl);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            $detailsResponse = curl_exec($ch);
                            curl_close($ch);

                            $movieDetails = json_decode($detailsResponse, true);

                            // Récupérer la durée du film
                            $runtime = isset($movieDetails['runtime']) ? $movieDetails['runtime'] : 'N/A';
                            $hours = floor($runtime / 60);
                            $minutes = $runtime % 60;
                            $formattedRuntime = $runtime !== 'N/A' ? "{$hours}h {$minutes}min" : 'Durée inconnue';

                            // Récupérer les catégories
                            $categories = [];
                            if (isset($movieDetails['genres'])) {
                                foreach ($movieDetails['genres'] as $genre) {
                                    $categories[] = htmlspecialchars($genre['name']);
                                }
                            }
                            $categoriesList = !empty($categories) ? implode(', ', $categories) : 'Aucune catégorie';

                            // Récupérer le réalisateur
                            $director = 'Inconnu';
                            if (isset($movieDetails['credits']['crew'])) {
                                foreach ($movieDetails['credits']['crew'] as $crewMember) {
                                    if ($crewMember['job'] === 'Director') {
                                        $director = htmlspecialchars($crewMember['name']);
                                        break;
                                    }
                                }
                            }
                            ?>
                            <!-- Affichage HTML des films -->
                            <div class="movie-card">
                                <div class="more-info">
                                    <p><strong>Réalisateur :</strong> <?= $director ?></p>
                                    <p><strong>Catégories :</strong> <?= $categoriesList ?></p>
                                    <p><strong>Durée :</strong> <?= $formattedRuntime ?></p>
                                    <p><strong>Note :</strong> <?= $rating ?>/10</p>
                                </div>
                                <div class="movie-poster">
                                    <img src="<?= $moviePoster ?>" alt="<?= $movieTitle ?>">
                                    <button class="toggle-info"><img src="assets/images/icon/arrowUp.svg" alt=""></button>
                                </div>
                                <h4><?= $movieTitle ?></h4>
                                <p><?= $releaseYear ?></p>
                            </div>

                            <?php
                        }
                    } else {
                        echo '<p>Aucun film tendance trouvé.</p>';
                    }
                    ?>
                </div>

            </div>
        </div>
    </main>

    <footer>


    </footer>
<script src="assets/js/script.js"></script>
</body>
</html>