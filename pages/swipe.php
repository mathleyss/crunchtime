<?php
session_start();

// Connexion à SQLite
$db = new SQLite3('../database/crunchtime.db');

$user = null; // Initialiser la variable utilisateur

// Vérifier si l'utilisateur est connecté
if (isset($_SESSION['user_id'])) {
    // Préparer et exécuter la requête pour récupérer les infos de l'utilisateur
    $stmt = $db->prepare("SELECT id, username, firstname, lastname, email, date FROM users WHERE id = :id");
    $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();

    $user = $result->fetchArray(SQLITE3_ASSOC);
}
// Récupération des médias dans la watchlist de l'utilisateur actuellement connecté
$apiKey = 'ad3586245e96a667f42a02c1b8708569';
$mediaDetails = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("SELECT media_id, media_type, added_at FROM watchlist WHERE user_id = :user_id ORDER BY added_at DESC");
    $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();

    $mediaList = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $mediaList[] = [
            'id' => $row['media_id'],
            'type' => $row['media_type'],
            'added_at' => $row['added_at']
        ];
    }

    // Récupérer les détails des médias depuis l'API
    foreach ($mediaList as $media) {
        $media_id = $media['id'];
        $media_type = $media['type'];

        $apiUrl = "https://api.themoviedb.org/3/{$media_type}/{$media_id}?api_key={$apiKey}&language=fr-FR";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

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
        <nav class="menu">
            <div class="menuLeft">
                <a href="../index.php" class="logoAccueil"> <img src="../assets/images/logo.png" alt=""></a>
                <a href="../index.php" class="linkAccueil">Accueil</a>
                <a href="<?php echo isset($_SESSION['user_id']) ? 'swipe.php' : 'login.php'; ?> "
                    id="active">CrunchSwipe</a>

            </div>
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
                        <img src="../assets/images/profile.png" alt="Profil" class="profile-img">
                        <div class="dropdown-menu">
                            <img src="../assets/images/profile.png" alt="">
                            <p><?= htmlspecialchars($user['username']) ?></p>
                            <a href="profile.php">Mon profil</a>
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
        <div class="headerContent">
            <h1>CrunchSwipe</h1>
            <p>Vous souhaitez découvrir un film ou une série ?</p>
            <p>Swipez pour ajouter à votre watchlist !</p>
        </div>
    </header>

    <main class="mainSwipe">
        <section id="swipeInfo"></section>

        <section id="swipeFeature">

            <div id="swiper"></div>
            <div class="swipeButtons">

                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" id="dislike">
                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                    <g id="SVGRepo_iconCarrier">
                        <path
                            d="M20 13H4C3.73478 13 3.48043 12.8946 3.29289 12.7071C3.10536 12.5196 3 12.2652 3 12C3 11.7348 3.10536 11.4804 3.29289 11.2929C3.48043 11.1054 3.73478 11 4 11H20C20.2652 11 20.5196 11.1054 20.7071 11.2929C20.8946 11.4804 21 11.7348 21 12C21 12.2652 20.8946 12.5196 20.7071 12.7071C20.5196 12.8946 20.2652 13 20 13Z"
                            fill="#000000"></path>
                    </g>
                </svg>
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" id="like">
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




        <section class="swipeWatchlist">
            <h2>Récemment ajouté</h2>
            <div class="watchlist-container" id="watchlist-container">
                <?php
                include 'get_watchlist.php';
                ?>
            </div>
            <a href="watchlist.php" class="swipeBtnWatchlist">Voir ma watchlist</a>
        </section>

    </main>

    <script src="../assets/js/card.js"></script>
    <script src="../assets/js/swipe.js"></script>
</body>