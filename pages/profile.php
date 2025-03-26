<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirige vers la page de connexion si non connecté
    exit;
}

// Connexion à SQLite
$db = new SQLite3('../database/crunchtime.db');

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

// Formater la date d'inscription
$formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
$date = $formatter->format(strtotime($user['date']));
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

    <!-- Lien favicons -->
    <link rel="icon" type="image/png" href="../assets/images/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon/favicon.svg" />
    <link rel="shortcut icon" href="../assets/images/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/images/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="CrunchTime" />
    <link rel="manifest" href="../assets/images/favicon/site.webmanifest" />

    <title>Mon profil - CrunchTime</title>
</head>

<body id="profilePage">
    <header>
        <nav class="menu menuOther">
            <div class="menuLeft">
                <a href="../index.php" class="logoAccueil"> <img src="../assets/images/logo.png" alt=""></a>
                <a href="../index.php">Accueil</a>
                <a href="<?php echo isset($_SESSION['user_id']) ? 'swipe.php' : 'login.php'; ?>">CrunchSwipe</a>
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
    </header>
    <main>
        <h2>Mon profil</h2>
        <div class="profileContainer">
            <div class="avatarContainer">
                <img src="../assets/images/profile.png" />
            </div>
            <div class="userInfo">
                <p class="userIdentity"><?= htmlspecialchars($user['firstname']) ?> <?= htmlspecialchars($user['lastname']) ?></p>

                <p class="userName"><strong>Nom d'utilisateur :</strong> <?= htmlspecialchars($user['username']) ?></p>

                <p class="userMail"><strong>Email :</strong> <?= htmlspecialchars($user['email']) ?></p>

                <p class="userRegisterDate">Tu es inscrit depuis le <?= htmlspecialchars($date) ?></p>
            </div>
        </div>
    </main>
</body>
</html>
