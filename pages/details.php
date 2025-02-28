<?php
session_start();

// Connexion à SQLite
$db = new SQLite3('../crunchtime.db');

// Clé API de TMDB
$apiKey = 'ad3586245e96a667f42a02c1b8708569';

// Récupérer l'ID et le type (film ou série) depuis l'URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : 'movie';

// Vérifier si l'ID est valide
if ($id === 0) {
    die("ID de film/série invalide.");
}

// Déterminer l'URL de l'API
$apiUrl = ($type === 'movie') ?
    "https://api.themoviedb.org/3/movie/$id?api_key=$apiKey&language=fr-FR&append_to_response=credits" :
    "https://api.themoviedb.org/3/tv/$id?api_key=$apiKey&language=fr-FR&append_to_response=credits";

// Récupération des détails
$response = file_get_contents($apiUrl);
$details = json_decode($response, true);

// Vérification des données
if (!$details || isset($details['status_code'])) {
    die("Impossible de récupérer les détails.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <title><?= htmlspecialchars($details['title'] ?? $details['name']) ?></title>
    <style>
        .details-container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background: #f8f8f8;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }
        .details-container img {
            max-width: 350px;
            height: auto;
            border-radius: 10px;
        }
        .movie-info {
            flex: 1;
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .movie-info p {
            font-size: 1.1rem;
            margin: 5px 0;
            color: #333;
            background: #fff;
            padding: 10px;
            border-radius: 5px;
        }
        .genres-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .genres-container span {
            background: #ddd;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9rem;
            font-weight: bold;
        }
    </style>
</head>
<body id="detailsPage">
<header>
    <nav class="menu menuOther">
        <div class="menuLeft">
            <a href="../index.php" class="logoAccueil"><img src="../assets/images/logo.png" alt=""></a>
            <a href="../index.php" id="active">Accueil</a>
            <a href="swipe.php">CrunchSwipe</a>
        </div>
        <div class="searchBar">
            <form action="search.php" method="GET">
                <img src="../assets/images/icon/search.svg" alt="Search">
                <input type="text" name="query" placeholder="Rechercher..." class="searchInput">
            </form>
        </div>
        <div class="menuRight">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="profile">
                    <img src="https://doodleipsum.com/700/avatar-2" alt="Profile" class="profile-img">
                    <div class="dropdown-menu">
                        <p><?= htmlspecialchars($_SESSION['username'] ?? 'Utilisateur') ?></p>
                        <a href="profile.php">Profil</a>
                        <a href="watchlist.php">Ma watchlist</a>
                        <a href="logout.php">Déconnexion</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="btnLogin">Connexion</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<main>
    <div class="details-container">
        <img src="https://image.tmdb.org/t/p/w500<?= $details['poster_path'] ?>" alt="<?= htmlspecialchars($details['title'] ?? $details['name']) ?>">

        <div class="movie-info">
            <h1><?= htmlspecialchars($details['title'] ?? $details['name']) ?></h1>
            <p><strong>Date de sortie :</strong> <?= date('d M Y', strtotime($details['release_date'] ?? $details['first_air_date'] ?? 'N/A')) ?></p>
            <p><strong>Note :</strong> <?= htmlspecialchars(number_format($details['vote_average'], 1)) ?>/10</p>
            <p><strong>Résumé :</strong> <?= htmlspecialchars($details['overview'] ?? 'Aucun résumé disponible.') ?></p>

            <?php if ($type === 'movie'): ?>
                <p><strong>Durée :</strong> <?= isset($details['runtime']) ? floor($details['runtime'] / 60) . "h " . $details['runtime'] % 60 . "min" : "Durée inconnue" ?></p>
            <?php else: ?>
                <p><strong>Nombre de saisons :</strong> <?= htmlspecialchars($details['number_of_seasons'] ?? 'Inconnu') ?></p>
            <?php endif; ?>

            <div class="genres-container">
                <strong>Genres :</strong>
                <?php if (!empty($details['genres'])): ?>
                    <?php foreach ($details['genres'] as $genre): ?>
                        <span>#<?= htmlspecialchars($genre['name']) ?></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span>Aucune information disponible</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<footer>
</footer>
</body>
</html>