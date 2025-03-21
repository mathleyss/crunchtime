<?php
session_start(); // Démarrer une session pour stocker l'utilisateur connecté

$message = ""; // Variable pour afficher les messages

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Connexion à SQLite
    $db = new SQLite3('../database/crunchtime.db');

    // Récupération des données du formulaire
    $username = isset($_POST['username']) ? trim($_POST['username']) : null;
    $password = isset($_POST['password']) ? $_POST['password'] : null;

    // Vérification que les champs ne sont pas vides
    if (!$username || !$password) {
        $message = "Veuillez remplir tous les champs.";
    } else {
        // Recherche de l'utilisateur dans la base de données
        $stmt = $db->prepare("SELECT id, username, password FROM users WHERE username = :username");
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();

        $user = $result->fetchArray(SQLITE3_ASSOC);

        // Si l'utilisateur existe
        if ($user) {
            // Vérification du mot de passe
            if (password_verify($password, $user['password'])) {
                // Mot de passe correct, on démarre la session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: ../index.php"); // Redirige vers une page de tableau de bord
                exit;
            } else {
                $message = "Mot de passe incorrect.";
            }
        } else {
            $message = "Utilisateur non trouvé.";
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
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

    <title>Connexion - CrunchTime</title>

    <!-- Lien favicons -->
    <link rel="icon" type="image/png" href="../assets/images/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon/favicon.svg" />
    <link rel="shortcut icon" href="../assets/images/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/images/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="CrunchTime" />
    <link rel="manifest" href="../assets/images/favicon/site.webmanifest" />
</head>

<body id="loginPage">
    <main>
        <div class="loginContainer">
            <div class="logoContainer">
                <a href="../index.php">
                    <img src="../assets/images/logo.png" alt="CrunchTime">
                </a>
                <h1>CrunchTime</h1>
            </div>

            <?php if (!empty($message)): ?>
                <p class='errorMessage'><?php echo $message; ?></p>
            <?php endif; ?>
            
            <form action="" method="post" class="loginForm">
                <div class="formInput">
                    <label for="username">Nom d'utilisateur :</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="formInput">
                    <label for="password">Mot de passe :</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div>
                    <button type="submit" class="submitButton">Connexion</button>
                </div>
            </form>
            <a href="register.php" class="registerLink">Créer un compte</a>
        </div>
    </main>
</body>

</html>
