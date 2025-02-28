<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirige vers la page de connexion si non connecté
    exit;
}

// Connexion à SQLite
$db = new SQLite3('../crunchtime.db');

// Préparer et exécuter la requête pour récupérer les infos de l'utilisateur
$stmt = $db->prepare("SELECT id, username, firstname, lastname, email, date FROM users WHERE id = :id");
$stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
$result = $stmt->execute();

$user = $result->fetchArray(SQLITE3_ASSOC);

// Vérifier si l'utilisateur existe bien en base de données
if (!$user) {
    echo "<p>Erreur : utilisateur introuvable.</p>";
    exit;
}

$date = date("d-m-Y", strtotime($user['date']));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <title>Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

</head>
<body id="profilePage">
<header>
    <nav class="menu menuOther">
        <div class="menuLeft">
            <a href="../index.php" class="logoAccueil"> <img src="/assets/images/logo.png" alt=""></a>
            <a href="../index.php">Accueil</a>
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
    <div class="logoContainer">
        <img src="../assets/images/logo.png" alt="CrunchTime">
        <h1>CrunchTime - Mon profil</h1>
    </div>


    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['username'])): ?>
        <p>ID Utilisateur : <strong><?= $_SESSION['user_id']; ?></strong></p>
        <a href="logout.php">Se déconnecter</a>
    <?php else: ?>
        <p>Pas de session active.</p>
        <a href="login.php">Se connecter</a>
    <?php endif; ?>

    <h2>Informations de votre compte :</h2>
    <div class="profileContainer">
        <div class="avatarContainer">
            <img src="https://doodleipsum.com/700x700/avatar-4?i=4c13721e99bcef1f9e61ac2af5b94936" alt="Avatar by Pablo Stanley" />
        </div>
        <div class="userInfo">
        <p><?= htmlspecialchars($user['firstname']) ?></p>
        <p><?= htmlspecialchars($user['lastname']) ?></p>
            <p><strong>Nom d'utilisateur :</strong> <?= htmlspecialchars($user['username']) ?></p>
            
            <p><strong>Email :</strong> <?= htmlspecialchars($user['email']) ?></p>
            <p>Inscrit depuis le <?= htmlspecialchars($date) ?></p>
        </div>
    </div>
    </main>
    
</body>
</html>