<?php
session_start();

// Connexion à SQLite
$db = new SQLite3('../database/crunchtime.db');
$user = null; // Initialiser la variable utilisateur

// Affichage des message concernant l'ajout d'un média à la watchlist
if (isset($_SESSION['message'])) {
    echo "<div class='messageWatchlist'>{$_SESSION['message']}</div>";
    // Supprimez le message après l'affichage pour ne pas l'afficher à nouveau sur les rechargements de page
    unset($_SESSION['message']);
}


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


// Fonction pour générer les étoiles
function generateStars($rating) {
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;

    $stars = str_repeat('★', $fullStars);
    if ($halfStar) {
        $stars .= '☆';
    }
    $stars .= str_repeat('☆', $emptyStars);

    return $stars;
}

// Récupération des médias dans la watchlist de l'utilisateur actuellement connecté
$watchlistMediaIds = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("SELECT media_id, media_type FROM watchlist WHERE user_id = :user_id");
    $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        // Créer une clé unique qui combine l'ID et le type
        $watchlistMediaIds[$row['media_id'] . '_' . $row['media_type']] = true;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <title>Détails</title>

    <!-- Lien favicons -->
    <link rel="icon" type="image/png" href="../assets/images/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon/favicon.svg" />
    <link rel="shortcut icon" href="../assets/images/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/images/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="CrunchTime" />
    <link rel="manifest" href="../assets/images/favicon/site.webmanifest" />
</head>

<body id="detailsPage">
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
        <div class="details-container">
            <?php if ($details): ?>
            <div class="details-poster">
                <img src="https://image.tmdb.org/t/p/w500<?= $details['poster_path'] ?>" alt="<?= htmlspecialchars($details['title'] ?? $details['name']) ?>">
            </div>
            <div class="details-content">
                <h1><?= htmlspecialchars($details['title'] ?? $details['name']) ?></h1>

                <!------
                    Gestion de la watchlist si l'utilisateur est connecté
                        ------>
                        <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="watchlist-action-container">
                        <!-- Icône du type de média -->
                        <div class="media-type-icon inline">
                            <?php if ($type === 'movie'): ?>
                                <!-- Icône de bobine pour les films -->
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                    <path d="M0 96C0 60.7 28.7 32 64 32H448c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96zM48 368v32c0 8.8 7.2 16 16 16H96c8.8 0 16-7.2 16-16V368c0-8.8-7.2-16-16-16H64c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16V368c0-8.8-7.2-16-16-16H416zM48 240v32c0 8.8 7.2 16 16 16H96c8.8 0 16-7.2 16-16V240c0-8.8-7.2-16-16-16H64c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16V240c0-8.8-7.2-16-16-16H416zM48 112v32c0 8.8 7.2 16 16 16H96c8.8 0 16-7.2 16-16V112c0-8.8-7.2-16-16-16H64c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16V112c0-8.8-7.2-16-16-16H416z"/>
                                </svg>
                            <?php else: ?>
                                <!-- Icône de TV pour les séries -->
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
                                    <path d="M64 64V352H576V64H64zM0 64C0 28.7 28.7 0 64 0H576c35.3 0 64 28.7 64 64V352c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V64zM128 448H512c17.7 0 32 14.3 32 32s-14.3 32-32 32H128c-17.7 0-32-14.3-32-32s14.3-32 32-32z"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <?php if (isset($watchlistMediaIds[$id . '_' . $type])): ?>
                        <!-- Bouton pour supprimer le média -->
                            <button class="watchlist-btn" data-id="<?= htmlspecialchars($id); ?>" data-action="remove" data-type="<?= htmlspecialchars($type); ?>">
                                Supprimer de la watchlist
                            </button>
                        <?php else: ?>
                        <!-- Bouton pour ajouter le média -->
                            <button class="watchlist-btn" data-id="<?= htmlspecialchars($id); ?>" data-action="add" data-type="<?= htmlspecialchars($type); ?>">
                                Ajouter à la watchlist
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <p><strong>Date de sortie :</strong> <?= date('d M Y', strtotime($details['release_date'] ?? $details['first_air_date'])) ?></p>
                <?php if ($type === 'movie'): ?>
                <p><strong>Durée :</strong> <?= floor($details['runtime'] / 60) ?>h <?= $details['runtime'] % 60 ?>min</p>
                <?php else: ?>
                <p><strong>Nombre de saisons :</strong> <?= htmlspecialchars($details['number_of_seasons']) ?></p>
                <?php endif; ?>
                <p><strong>Note :</strong> <?= generateStars($details['vote_average'] / 2) ?></p>
                <p><strong>Genres :</strong> <?= implode(', ', array_map(function($genre) { return htmlspecialchars($genre['name']); }, $details['genres'])) ?></p>
                <p><strong>Réalisateur :</strong> <?= htmlspecialchars($details['credits']['crew'][0]['name'] ?? 'Inconnu') ?></p>
                <p class="details-resume"> <?= htmlspecialchars($details['overview']) ?></p>
                
                <!-- Affichage des acteurs -->
                <h2>Acteurs</h2>
                <div class="actors-container">
                    <?php foreach (array_slice($details['credits']['cast'], 0, 10) as $actor): ?>
                    <div class="actor">
                        <img src="https://image.tmdb.org/t/p/w200<?= $actor['profile_path'] ?>" alt="<?= htmlspecialchars($actor['name']) ?>">
                        <p><strong><?= htmlspecialchars($actor['name']) ?></strong></p>
                        <p><?= htmlspecialchars($actor['character']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>


                <?php else: ?>
                <p>Détails non disponibles.</p>
                <?php endif; ?>


            </div>
        </div>
    </main>
<script src="../assets/js/watchlist.js"></script>
</body>
</html>
