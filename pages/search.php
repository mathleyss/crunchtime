<?php
session_start();

// Connexion à SQLite
$db = new SQLite3('../crunchtime.db');

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap"
        rel="stylesheet">
    <title>Recherche</title>
</head>

<body>
<header>
    <nav class="menu menuOther">
        <div class="menuLeft">
            <a href="../index.php" class="logoAccueil"> <img src="/assets/images/logo.png" alt=""></a>
            <a href="../index.php">Accueil</a>
            <a href="swipe.php" id="active">CrunchSwipe</a>
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