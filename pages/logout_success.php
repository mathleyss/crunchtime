<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../assets/css/styles.css">

    <title>Déconnexion - CrunchTime</title>

    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap"
        rel="stylesheet">

    <!-- Lien vers les favicons pour différents appareils et navigateurs -->
    <link rel="icon" type="image/png" href="../assets/images/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon/favicon.svg" />
    <link rel="shortcut icon" href="../assets/images/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/images/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="CrunchTime" />
    <link rel="manifest" href="../assets/images/favicon/site.webmanifest" />

    <script>
        // Redirige automatiquement l'utilisateur vers la page d'accueil après 1 seconde
        setTimeout(function () {
            window.location.href = "../index.php";
        }, 1000);
    </script>
</head>

<body id="logoutSuccess">
    <main role="main">
        <div class="loginContainer">
            <!-- Section contenant le logo et le titre -->
            <div class="logoContainer" role="banner">
                <!-- Lien vers la page d'accueil via le logo -->
                <a href="../index.php" aria-label="Retour à la page d'accueil">
                    <img src="../assets/images/logo.png" alt="Logo de CrunchTime">
                </a>
                <h1>CrunchTime</h1>
            </div>

            <!-- Message de confirmation de déconnexion -->
            <div class="logout-message" role="alert">
                <h2>Déconnexion réussie !</h2>
                <p>Redirection en cours vers la page d'accueil...</p>

                <!-- Animation de chargement -->
                <div class="loader" role="status" aria-label="Chargement en cours"></div>
            </div>
        </div>
    </main>
</body>

</html>