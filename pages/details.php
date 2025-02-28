<?php
session_start();

// Connexion à SQLite
$db = new SQLite3('crunchtime.db');

// Clé API de TMDB (remplace-la par la tienne)
$apiKey = 'ad3586245e96a667f42a02c1b8708569';

// Récupérer l'ID et le type (film ou série) depuis l'URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : 'movie';

// Déterminer l'URL de l'API en fonction du type
if ($type === 'movie') {
    $apiUrl = "https://api.themoviedb.org/3/movie/$id?api_key=$apiKey&language=fr-FR&append_to_response=credits";
} else {
    $apiUrl = "https://api.themoviedb.org/3/tv/$id?api_key=$apiKey&language=fr-FR&append_to_response=credits";
}

// Initialiser cURL pour récupérer les détails
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// Exécuter la requête et obtenir la réponse JSON
$response = curl_exec($ch);
curl_close($ch);

// Décoder la réponse JSON en tableau associatif PHP
$details = json_decode($response, true);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <title>Détails</title>
</head>
<body id="detailsPage">
<header>
    <nav class="menu menuOther">
        <div class="menuLeft">
            <a href="../index.php" class="logoAccueil"> <img src="/assets/images/logo.png" alt=""></a>
            <a href="../index.php" id="active">Accueil</a>
            <a href="swipe.php">CrunchSwipe</a>
        </div>
        <!-- BARRE DE RECHERCHE À REFAIRE ET EN CSS AUSSI -->
        <div class="searchBar">
            <form action="search.php" method="GET">

                <img src="/assets/images/icon/search.svg" alt="Search">

                <input type="text" name="query" placeholder="Rechercher..." class="searchInput">
            </form>
        </div>
        <div class="menuRight">

            <!-- Si un utilisateur est connecté, alors ... -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="profile">
                    <img src="https://doodleipsum.com/700/avatar-2?i=6197810111afde5fbb243bac8463665e" alt="Profile"
                        class="profile-img">
                    <div class="dropdown-menu">
                        <img src="https://doodleipsum.com/700/avatar-2?i=6197810111afde5fbb243bac8463665e" alt="">
                        <p><?= htmlspecialchars($user['username']) ?></p>
                        <a href="pages/profile.php">Profil</a>
                        <a href="pages/watchlist.php">Ma watchlist</a>
                        <a href="pages/logout.php" id="logout">Déconnexion</a>
                    </div>
                </div>
                <!-- ... Sinon ... -->
            <?php else: ?>
                <a href="pages/login.php" class="btnLogin">
                    Connexion
                </a>
            <?php endif; ?>
        </div>
    </nav>
</header>

    <main>
        <div class="details-container">
            <?php if ($details): ?>
                <h1><?= htmlspecialchars($details['title'] ?? $details['name']) ?></h1>
                <img src="https://image.tmdb.org/t/p/w500<?= $details['poster_path'] ?>" alt="<?= htmlspecialchars($details['title'] ?? $details['name']) ?>">
                <p><strong>Date de sortie :</strong> <?= date('d M Y', strtotime($details['release_date'] ?? $details['first_air_date'])) ?></p>
                <p><strong>Note :</strong> <?= htmlspecialchars($details['vote_average']) ?>/10</p>
                <p><strong>Résumé :</strong> <?= htmlspecialchars($details['overview']) ?></p>
                <?php if ($type === 'movie'): ?>
                    <p><strong>Durée :</strong> <?= floor($details['runtime'] / 60) ?>h <?= $details['runtime'] % 60 ?>min</p>
                <?php else: ?>
                    <p><strong>Nombre de saisons :</strong> <?= htmlspecialchars($details['number_of_seasons']) ?></p>
                <?php endif; ?>
                <p><strong>Genres :</strong> <?= implode(', ', array_map(function($genre) { return htmlspecialchars($genre['name']); }, $details['genres'])) ?></p>
                <p><strong>Réalisateur :</strong> <?= htmlspecialchars($details['credits']['crew'][0]['name'] ?? 'Inconnu') ?></p>
            <?php else: ?>
                <p>Détails non disponibles.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer>
    </footer>
</body>
</html>