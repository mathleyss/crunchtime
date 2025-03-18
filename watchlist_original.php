<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Connexion à SQLite
$db = new SQLite3('../database/crunchtime.db');

// Clé API de TMDB
$apiKey = 'ad3586245e96a667f42a02c1b8708569';

// Récupération des informations de l'utilisateur
$stmt = $db->prepare("SELECT id, username, firstname, lastname, email, date FROM users WHERE id = :id");
$stmt->bindValue(':id', $user_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$user = $result->fetchArray(SQLITE3_ASSOC);

// Récupération des médias dans la watchlist de l'utilisateur actuellement connecté
$stmt = $db->prepare("SELECT media_id FROM watchlist WHERE user_id = :user_id");
$stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
$result = $stmt->execute();

// Collecter tous les IDs de médias
$mediaIds = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $mediaIds[] = $row['media_id'];
}

// Tableau pour stocker les détails des médias
$mediaDetails = [];

// Pour chaque ID de média, récupérer les détails depuis l'API
foreach ($mediaIds as $media_id) {
    // Essayer d'abord comme un film
    $movieUrl = "https://api.themoviedb.org/3/movie/{$media_id}?api_key={$apiKey}&language=fr-FR&append_to_response=credits";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $movieUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $details = json_decode($response, true);
    
    // Si c'est un film valide
    if (isset($details['title'])) {
        $mediaDetails[] = [
            'id' => $media_id,
            'title' => $details['title'],
            'poster_path' => $details['poster_path'],
            'release_date' => $details['release_date'],
            'vote_average' => $details['vote_average'],
            'overview' => $details['overview'],
            'media_type' => 'movie',
            'director' => getDirector($details),
            'runtime' => $details['runtime'],
            'genres' => implode(', ', array_map(function($genre) { return $genre['name']; }, $details['genres']))
        ];
    } else {
        // Sinon, essayer comme une série
        $tvUrl = "https://api.themoviedb.org/3/tv/{$media_id}?api_key={$apiKey}&language=fr-FR&append_to_response=credits";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tvUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $details = json_decode($response, true);
        
        // Si c'est une série valide
        if (isset($details['name'])) {
            $mediaDetails[] = [
                'id' => $media_id,
                'title' => $details['name'],
                'poster_path' => $details['poster_path'],
                'release_date' => $details['first_air_date'],
                'vote_average' => $details['vote_average'],
                'overview' => $details['overview'],
                'media_type' => 'tv',
                'seasons' => $details['number_of_seasons'],
                'genres' => implode(', ', array_map(function($genre) { return $genre['name']; }, $details['genres']))
            ];
        }
    }
}

// Fonction pour extraire le réalisateur
function getDirector($details) {
    if (isset($details['credits']['crew'])) {
        foreach ($details['credits']['crew'] as $crew) {
            if ($crew['job'] === 'Director') {
                return $crew['name'];
            }
        }
    }
    return 'Inconnu';
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <title>Watchlist</title>

    <!-- Lien favicons -->
    <link rel="icon" type="image/png" href="../assets/images/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon/favicon.svg" />
    <link rel="shortcut icon" href="../assets/images/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/images/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="CrunchTime" />
    <link rel="manifest" href="../assets/images/favicon/site.webmanifest" />
</head>

<body class="page-watchlist">
    <header>
        <nav class="menu menuOther">
            <div class="menuLeft">
                <a href="../index.php" class="logoAccueil"> <img src="../assets/images/logo.png" alt=""></a>
                <a href="../index.php">Accueil</a>
                <a href="swipe.php">CrunchSwipe</a>
            </div>
            <!-- BARRE DE RECHERCHE À REFAIRE ET EN CSS AUSSI -->
            <div class="searchBar">
                <form action="search.php" method="GET">

                    <img src="../assets/images/icon/search.svg" alt="Search">

                    <input type="text" name="query" placeholder="Rechercher..." class="searchInput" required>
                </form>
            </div>
            <div class="menuRight">

                <!-- Si un utilisateur est connecté, alors ... -->
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="profile">
                    <img src="https://doodleipsum.com/700/avatar-2?i=6197810111afde5fbb243bac8463665e" alt="Profile" class="profile-img">
                    <div class="dropdown-menu">
                        <img src="https://doodleipsum.com/700/avatar-2?i=6197810111afde5fbb243bac8463665e" alt="">
                        <p><?= htmlspecialchars($user['username']) ?></p>
                        <a href="profile.php">Profil</a>
                        <a href="watchlist.php">Ma watchlist</a>
                        <a href="logout.php" id="logout">Déconnexion</a>
                    </div>
                </div>
                <!-- ... Sinon ... -->
                <?php else: ?>
                <a href="login.php" class="btnLogin">
                    Connexion
                </a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    <main>
        <!-- Affichage des films ajoutés à la watchlist de l'utilisateur -->
        <section class="watchlist">
            <div class="watchlistTop">
                <h2>Votre watchlist</h2>
                <div class="buttonNav">
                    <img src="../assets/images/icon/arrowLeft.svg" alt="" class="prev action-prev">
                    <img src="../assets/images/icon/arrowRight.svg" class="next action-next" alt="">
                </div>

                <?php if (empty($mediaDetails)): ?>
                <!-- On affiche ce message si l'utilisateur n'a pas de films dans sa watchlist -->
                <p class="erreurWatchlist">Vous n'avez actuellement aucun film dans votre watchlist.</p>
                <?php else: ?>
            </div>

            <div class="carousel-container">
                <div class="carousel">
                    <?php foreach ($mediaDetails as $media): ?>
                    <div class="movie-card">
                        <div class="movie-poster">
                            <a href="details.php?id=<?= $media['id'] ?>&type=<?= $media['media_type'] ?>">
                                <img src="https://image.tmdb.org/t/p/w500<?= $media['poster_path'] ?>" alt="<?= htmlspecialchars($media['title']) ?>">
                            </a>
                            <h4><?= htmlspecialchars($media['title']) ?></h4>
                            <p><?= date('Y', strtotime($media['release_date'])) ?></p>

                            <!-- Formulaire pour supprimer le média  -->
                            <form method="post" action="suppression_watchlist_ancien.php">
                                <input type="hidden" name="media_id" value="<?= $media['id'] ?>">
                                <!-- From Uiverse.io by vinodjangid07 -->
                                <div class="btnWatchlist btnWatchlistDel">
                                    <button class="button" type="submit">
                                        <svg viewBox="0 0 448 512" class="svgIconBtn">
                                            <path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
        </section>
    </main>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/watchlist.js"></script>
</body>

</html>
