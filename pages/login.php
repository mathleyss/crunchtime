<?php
session_start(); // Démarre une session pour stocker les informations de l'utilisateur connecté

$message = ""; // Variable utilisée pour afficher des messages d'erreur ou d'information

if ($_SERVER["REQUEST_METHOD"] == "POST") { // Vérifie si le formulaire a été soumis
    // Connexion à la base de données SQLite
    $db = new SQLite3('../database/crunchtime.db');

    // Récupération des données du formulaire
    $username = isset($_POST['username']) ? trim($_POST['username']) : null; // Nom d'utilisateur saisi
    $password = isset($_POST['password']) ? $_POST['password'] : null; // Mot de passe saisi

    // Vérifie que les champs ne sont pas vides
    if (!$username || !$password) {
        $message = "Veuillez remplir tous les champs."; // Message d'erreur si un champ est vide
    } else {
        // Prépare une requête pour rechercher l'utilisateur dans la base de données
        $stmt = $db->prepare("SELECT id, username, password FROM users WHERE username = :username");
        $stmt->bindValue(':username', $username, SQLITE3_TEXT); // Lie la valeur du nom d'utilisateur
        $result = $stmt->execute(); // Exécute la requête

        $user = $result->fetchArray(SQLITE3_ASSOC); // Récupère les données de l'utilisateur sous forme de tableau associatif

        // Vérifie si l'utilisateur existe
        if ($user) {
            // Vérifie si le mot de passe saisi correspond au mot de passe haché dans la base de données
            if (password_verify($password, $user['password'])) {
                // Si le mot de passe est correct, on initialise la session utilisateur
                $_SESSION['user_id'] = $user['id']; // Stocke l'ID de l'utilisateur dans la session
                $_SESSION['username'] = $user['username']; // Stocke le nom d'utilisateur dans la session
                header("Location: ../index.php"); // Redirige vers la page d'accueil ou tableau de bord
                exit; // Termine le script après la redirection
            } else {
                $message = "Mot de passe incorrect."; // Message d'erreur si le mot de passe est incorrect
            }
        } else {
            $message = "Utilisateur non trouvé."; // Message d'erreur si l'utilisateur n'existe pas
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap"
        rel="stylesheet">

    <title>Connexion - CrunchTime</title>

    <!-- Lien favicons pour les icônes du site -->
    <link rel="icon" type="image/png" href="../assets/images/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon/favicon.svg" />
    <link rel="shortcut icon" href="../assets/images/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/images/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="CrunchTime" />
    <link rel="manifest" href="../assets/images/favicon/site.webmanifest" />
</head>

<body id="loginPage">
    <main role="main">
        <div class="loginContainer" role="form" aria-label="Formulaire de connexion">
            <div class="logoContainer">
                <!-- Logo et titre du site -->
                <a href="../index.php" aria-label="Retour à l'accueil">
                    <img src="../assets/images/logo.png" alt="Logo de CrunchTime">
                </a>
                <h1>CrunchTime</h1>
            </div>

            <!-- Affiche un message d'erreur ou d'information si nécessaire -->
            <?php if (!empty($message)): ?>
                <p class='errorMessage' role="alert"><?php echo $message; ?></p>
            <?php endif; ?>

            <!-- Formulaire de connexion -->
            <form action="" method="post" class="loginForm" aria-label="Connexion à votre compte">
                <div class="formInput">
                    <label for="username">Nom d'utilisateur :</label>
                    <input type="text" id="username" name="username" required aria-required="true" aria-label="Nom d'utilisateur"> <!-- Champ pour le nom d'utilisateur -->
                </div>
                <div class="formInput">
                    <label for="password">Mot de passe :</label>
                    <input type="password" id="password" name="password" required aria-required="true" aria-label="Mot de passe"> <!-- Champ pour le mot de passe -->
                </div>
                <div>
                    <button type="submit" class="submitButton" aria-label="Se connecter">Connexion</button> <!-- Bouton pour soumettre le formulaire -->
                </div>
            </form>
            <a href="register.php" class="registerLink" aria-label="Créer un compte utilisateur">Créer un compte</a> <!-- Lien vers la page d'inscription -->
        </div>
    </main>
</body>

</html>