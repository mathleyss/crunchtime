<?php
session_start(); // Démarrer la session pour gérer les données utilisateur

// Connexion à la base de données SQLite
$db = new SQLite3('../database/crunchtime.db');

$user = null; // Initialiser la variable utilisateur à null

// Vérifier si un utilisateur est connecté via la session
if (isset($_SESSION['user_id'])) {
    // Préparer une requête pour récupérer les informations de l'utilisateur connecté
    $stmt = $db->prepare("SELECT id, username, firstname, lastname, email, date FROM users WHERE id = :id");
    $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER); // Associer l'ID utilisateur à la requête
    $result = $stmt->execute(); // Exécuter la requête

    $user = $result->fetchArray(SQLITE3_ASSOC); // Récupérer les données utilisateur sous forme de tableau associatif
}

// Clé API pour interagir avec l'API The Movie Database (TMDb)
$apiKey = 'ad3586245e96a667f42a02c1b8708569';
$mediaDetails = []; // Tableau pour stocker les détails des médias

// Vérifier si un utilisateur est connecté pour récupérer sa watchlist
if (isset($_SESSION['user_id'])) {
    // Préparer une requête pour récupérer les médias de la watchlist de l'utilisateur
    $stmt = $db->prepare("SELECT media_id, media_type, added_at FROM watchlist WHERE user_id = :user_id ORDER BY added_at DESC");
    $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER); // Associer l'ID utilisateur à la requête
    $result = $stmt->execute(); // Exécuter la requête

    $mediaList = []; // Tableau pour stocker les médias de la watchlist
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        // Ajouter chaque média à la liste avec ses informations
        $mediaList[] = [
            'id' => $row['media_id'],
            'type' => $row['media_type'],
            'added_at' => $row['added_at']
        ];
    }

    // Parcourir la liste des médias pour récupérer leurs détails via l'API TMDb
    foreach ($mediaList as $media) {
        $media_id = $media['id']; // ID du média
        $media_type = $media['type']; // Type du média (film ou série)

        // Construire l'URL de l'API pour récupérer les détails du média
        $apiUrl = "https://api.themoviedb.org/3/{$media_type}/{$media_id}?api_key={$apiKey}&language=fr-FR";
        $ch = curl_init(); // Initialiser une session cURL
        curl_setopt($ch, CURLOPT_URL, $apiUrl); // Définir l'URL de l'API
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Retourner la réponse sous forme de chaîne
        $response = curl_exec($ch); // Exécuter la requête API
        curl_close($ch); // Fermer la session cURL

        // Décoder la réponse JSON et l'ajouter au tableau des détails des médias
        $mediaDetails[] = json_decode($response, true);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap"
        rel="stylesheet">
    <title>CrunchSwipe - CrunchTime</title>

    <!-- Lien favicons -->
    <link rel="icon" type="image/png" href="../assets/images/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon/favicon.svg" />
    <link rel="shortcut icon" href="../assets/images/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/images/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="CrunchTime" />
    <link rel="manifest" href="../assets/images/favicon/site.webmanifest" />
</head>

<body id="swipePage">
    <header>
        <nav class="menu" role="navigation" aria-label="Menu principal">
            <!-- Menu de navigation -->
            <div class="menuLeft">
                <a href="../index.php" class="logoAccueil" aria-label="Retour à l'accueil">
                    <img src="../assets/images/logo.png" alt="Logo CrunchTime">
                </a>
                <a href="../index.php" class="linkAccueil" aria-label="Lien vers la page d'accueil">Accueil</a>
                <a href="<?php echo isset($_SESSION['user_id']) ? 'swipe.php' : 'login.php'; ?>" id="active"
                    aria-label="Lien vers CrunchSwipe">CrunchSwipe</a>
            </div>
            <div class="searchBar">
                <form action="search.php" method="GET" role="search" aria-label="Barre de recherche">
                    <img src="../assets/images/icon/search.svg" alt="Icône de recherche">
                    <input type="text" name="query" placeholder="Rechercher..." class="searchInput" required
                        aria-label="Champ de recherche">
                </form>
            </div>
            <div class="menuRight">
                <!-- Si un utilisateur est connecté, alors ... -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="profile" role="menu" aria-label="Menu utilisateur">
                        <img src="../assets/images/profile.png" alt="Image de profil" class="profile-img">
                        <div class="dropdown-menu">
                            <img src="../assets/images/profile.png" alt="Image de profil utilisateur">
                            <p><?= htmlspecialchars($user['username']) ?></p>
                            <a href="profile.php" aria-label="Lien vers mon profil">Mon profil</a>
                            <a href="watchlist.php" aria-label="Lien vers ma watchlist">Ma watchlist</a>
                            <a href="logout.php" id="logout" aria-label="Déconnexion">Déconnexion</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btnLogin" aria-label="Lien vers la page de connexion">
                        Connexion
                    </a>
                <?php endif; ?>
            </div>
        </nav>
        <div class="headerContent">
            <!-- Contenu principal de l'en-tête -->
            <h1>CrunchSwipe</h1>
            <p>Vous souhaitez découvrir un film ou une série ?</p>
            <p>Swipez pour ajouter à votre watchlist !</p>
        </div>
    </header>

    <main class="mainSwipe" role="main">
        <section id="swipeInfo" aria-label="Informations sur le swipe"></section>

        <section id="swipeFeature" aria-label="Fonctionnalité de swipe">
            <!-- Section pour le swipe avec les boutons "like" et "dislike" -->
            <div id="swiper" role="region" aria-label="Zone de swipe"></div>
            <div class="swipeButtons" role="group" aria-label="Boutons de swipe">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" id="dislike" role="button"
                    aria-label="Bouton dislike">
                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                    <g id="SVGRepo_iconCarrier">
                        <path
                            d="M20 13H4C3.73478 13 3.48043 12.8946 3.29289 12.7071C3.10536 12.5196 3 12.2652 3 12C3 11.7348 3.10536 11.4804 3.29289 11.2929C3.48043 11.1054 3.73478 11 4 11H20C20.2652 11 20.5196 11.1054 20.7071 11.2929C20.8946 11.4804 21 11.7348 21 12C21 12.2652 20.8946 12.5196 20.7071 12.7071C20.5196 12.8946 20.2652 13 20 13Z"
                            fill="#000000"></path>
                    </g>
                </svg>
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" id="like" role="button"
                    aria-label="Bouton like">
                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                    <g id="SVGRepo_iconCarrier">
                        <path
                            d="M12.75 11.25V5C12.75 4.80109 12.671 4.61032 12.5303 4.46967C12.3897 4.32902 12.1989 4.25 12 4.25C11.8011 4.25 11.6103 4.32902 11.4697 4.46967C11.329 4.61032 11.25 4.80109 11.25 5V11.25H5C4.80109 11.25 4.61032 11.329 4.46967 11.4697C4.32902 11.6103 4.25 11.8011 4.25 12C4.25 12.1989 4.32902 12.3897 4.46967 12.5303C4.61032 12.671 4.80109 12.75 5 12.75H11.25V19C11.2526 19.1981 11.3324 19.3874 11.4725 19.5275C11.6126 19.6676 11.8019 19.7474 12 19.75C12.1989 19.75 12.3897 19.671 12.5303 19.5303C12.671 19.3897 12.75 19.1989 12.75 19V12.75H19C19.1989 12.75 19.3897 12.671 19.5303 12.5303C19.671 12.3897 19.75 12.1989 19.75 12C19.7474 11.8019 19.6676 11.6126 19.5275 11.4725C19.3874 11.3324 19.1981 11.2526 19 11.25H12.75Z"
                            fill="#000000"></path>
                    </g>
                </svg>
            </div>
        </section>

        <section class="swipeWatchlist" aria-label="Watchlist récemment ajoutée">
            <h2>Récemment ajouté</h2>
            <div class="watchlist-container" id="watchlist-container" role="region" aria-label="Conteneur de watchlist">
                <?php
                // Inclure le fichier pour afficher la watchlist
                include 'get_watchlist.php';
                ?>
            </div>
            <a href="watchlist.php" class="swipeBtnWatchlist" aria-label="Voir ma watchlist">Voir ma watchlist</a>
        </section>
    </main>

    <script src="../assets/js/card.js"></script> <!-- Script pour gérer les cartes -->
    <script src="../assets/js/swipe.js"></script> <!-- Script pour gérer le swipe -->
</body>
</html>