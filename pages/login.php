<?php
session_start(); // Démarrer une session pour stocker l'utilisateur connecté

$message = ""; // Variable pour afficher les messages

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Connexion à SQLite
    $db = new SQLite3('../crunchtime.db');

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
</head>
<body id="loginPage">
    <div class="loginContainer">
        <div class="logoContainer">
            <img src="../assets/images/logo.png" alt="CrunchTime">
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
    

</body>
</html>