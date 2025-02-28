<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <title>Déconnexion</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

    <script>
        // Redirige vers la page d'accueil après 3 secondes
        setTimeout(function() {
            window.location.href = "../index.php";
        }, 1000);
    </script>
</head>
<body>
    <div class="logout-message">
        <h1>Vous avez été déconnecté</h1>
        <p>Vous allez être redirigé vers la page d'accueil dans quelques secondes...</p>
    </div>
</body>
</html>