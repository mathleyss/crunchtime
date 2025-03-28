<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <title>Déconnexion - CrunchTime</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap"
        rel="stylesheet">

    <!-- Lien favicons -->
    <link rel="icon" type="image/png" href="../assets/images/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon/favicon.svg" />
    <link rel="shortcut icon" href="../assets/images/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/images/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="CrunchTime" />
    <link rel="manifest" href="../assets/images/favicon/site.webmanifest" />

    <script>
        // Redirige vers la page d'accueil
        setTimeout(function () {
            window.location.href = "../index.php";
        }, 1000);

    </script>
</head>

<body id="logoutSuccess">
    <main>
        <div class="loginContainer">
            <div class="logoContainer">
                <a href="../index.php">
                    <img src="../assets/images/logo.png" alt="CrunchTime">
                </a>
                <h1>CrunchTime</h1>
            </div>

            <div class="logout-message">
                <h2>Déconnexion réussie !</h2>
                <p>Redirection en cours vers la page d'accueil...</p>

                <div class="loader"></div>
            </div>
        </div>
    </main>
</body>

</html>