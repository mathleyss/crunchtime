<?php
$message = ""; // Variable pour afficher les messages d'erreur
$successMessage = ""; // Variable pour afficher les messages de succès

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Connexion à SQLite
    $db = new SQLite3('../database/crunchtime.db');

    // Vérification que toutes les données existent avant de les utiliser
    $prenom = isset($_POST['firstname']) ? trim($_POST['firstname']) : null;
    $nom = isset($_POST['lastname']) ? trim($_POST['lastname']) : null;
    $username = isset($_POST['username']) ? trim($_POST['username']) : null;
    $email = isset($_POST['email']) ? trim($_POST['email']) : null;
    $password = isset($_POST['password']) ? $_POST['password'] : null;

    // Vérifier qu'aucune donnée n'est vide
    if (!$prenom || !$nom || !$username || !$email || !$password) {
        $message = "Veuillez remplir tous les champs.";
    } else {
        // Vérification si l'utilisateur existe déjà
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();

        if ($result->fetchArray()) {
            $message = "Ce nom d'utilisateur ou cet email existe déjà.";
        } else {
            // Hachage du mot de passe
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            // Insertion dans la base de données
            $stmt = $db->prepare("INSERT INTO users (firstname, lastname, username, email, password) 
                                  VALUES (:firstname, :lastname, :username, :email, :password)");
            $stmt->bindValue(':firstname', $prenom, SQLITE3_TEXT);
            $stmt->bindValue(':lastname', $nom, SQLITE3_TEXT);
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $stmt->bindValue(':password', $passwordHash, SQLITE3_TEXT);

            if ($stmt->execute()) {
                $successMessage = "Inscription réussie ! <a href='login.php'>Connectez-vous ici</a>";
            } else {
                $message = "Erreur lors de l'inscription.";
            }
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
    <title>Inscription - CrunchTime</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

    <!-- Lien favicons -->
    <link rel="icon" type="image/png" href="../assets/images/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon/favicon.svg" />
    <link rel="shortcut icon" href="../assets/images/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/images/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="CrunchTime" />
    <link rel="manifest" href="../assets/images/favicon/site.webmanifest" />
</head>

<body id="registerPage">
    <div class="loginContainer">
        <div class="logoContainer">
            <img src="../assets/images/logo.png" alt="CrunchTime">
            <h1>CrunchTime</h1>
        </div>

        <!-- Affichage des différents messages d'erreur -->
        <?php if (!empty($message)): ?>
        <p class='errorMessage'><?php echo $message; ?></p>
        <?php endif; ?>

        <!-- Affichage des différents messages de succès -->
        <?php if (!empty($successMessage)): ?>
        <p class='successMessage'><?php echo $successMessage; ?></p>
        <?php endif; ?>

        <form action="" method="post" class="loginForm">
            <h2>Inscription</h2>
            <div class="formInput">
                <label for="firstname">Prénom :</label>
                <input type="text" id="firstname" name="firstname" required>
            </div>
            <div class="formInput">
                <label for="lastname">Nom :</label>
                <input type="text" id="lastname" name="lastname" required>
            </div>
            <div class="formInput">
                <label for="username">Nom d'utilisateur :</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="formInput">
                <label for="email">Email :</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="formInput">
                <label for="password">Mot de passe :</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div>
                <button type="submit" class="submitButton">S'inscrire</button>
            </div>
        </form>
    </div>


</body>

</html>
