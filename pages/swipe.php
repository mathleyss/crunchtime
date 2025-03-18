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
    <title>CrunchSwipe</title>

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
                <a href="../index.php">Accueil</a>
                <a href="swipe.php" id="active">CrunchSwipe</a>
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
                        <img src="https://doodleipsum.com/700/avatar-2?i=6197810111afde5fbb243bac8463665e" alt="Profile"
                            class="profile-img">
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
            <ion-icon id="dislike" name="heart-dislike"></ion-icon>
            <ion-icon id="like" name="heart"></ion-icon>
            </div>


        </section>





        <section class="swipeWatchlist">

<p>En attente de l'affichage de la watchlist</p>
        </section>
    </main>

    <script src="../assets/js/card.js"></script>
    <script src="../assets/js/swipe.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>