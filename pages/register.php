<?php
$message = ""; // Variable pour afficher les messages d'erreur
$successMessage = ""; // Variable pour afficher les messages de succès

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Connexion à la base de données SQLite
    $db = new SQLite3('../database/crunchtime.db');

    // Récupération et nettoyage des données envoyées par le formulaire
    $prenom = isset($_POST['firstname']) ? trim($_POST['firstname']) : null; // Prénom
    $nom = isset($_POST['lastname']) ? trim($_POST['lastname']) : null; // Nom
    $username = isset($_POST['username']) ? trim($_POST['username']) : null; // Nom d'utilisateur
    $email = isset($_POST['email']) ? trim($_POST['email']) : null; // Email
    $password = isset($_POST['password']) ? $_POST['password'] : null; // Mot de passe

    // Vérification que tous les champs sont remplis
    if (!$prenom || !$nom || !$username || !$email || !$password) {
        $message = "Veuillez remplir tous les champs."; // Message d'erreur si un champ est vide
    } else {
        // Vérification si un utilisateur avec le même email ou nom d'utilisateur existe déjà
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
        $stmt->bindValue(':email', $email, SQLITE3_TEXT); // Liaison de l'email
        $stmt->bindValue(':username', $username, SQLITE3_TEXT); // Liaison du nom d'utilisateur
        $result = $stmt->execute();

        if ($result->fetchArray()) {
            $message = "Ce nom d'utilisateur ou cet email existe déjà."; // Message d'erreur si l'utilisateur existe
        } else {
            // Hachage du mot de passe pour plus de sécurité
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            // Insertion des données de l'utilisateur dans la base de données
            $stmt = $db->prepare("INSERT INTO users (firstname, lastname, username, email, password) 
                                  VALUES (:firstname, :lastname, :username, :email, :password)");
            $stmt->bindValue(':firstname', $prenom, SQLITE3_TEXT); // Liaison du prénom
            $stmt->bindValue(':lastname', $nom, SQLITE3_TEXT); // Liaison du nom
            $stmt->bindValue(':username', $username, SQLITE3_TEXT); // Liaison du nom d'utilisateur
            $stmt->bindValue(':email', $email, SQLITE3_TEXT); // Liaison de l'email
            $stmt->bindValue(':password', $passwordHash, SQLITE3_TEXT); // Liaison du mot de passe haché

            if ($stmt->execute()) {
                // Message de succès si l'inscription est réussie
                $successMessage = "Inscription réussie ! <a href='login.php' class='loginLink'>Connectez-vous ici</a>";
            } else {
                $message = "Erreur lors de l'inscription."; // Message d'erreur en cas de problème d'insertion
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
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap"
        rel="stylesheet">

    <!-- Lien favicons -->
    <link rel="icon" type="image/png" href="../assets/images/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon/favicon.svg" />
    <link rel="shortcut icon" href="../assets/images/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/images/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="CrunchTime" />
    <link rel="manifest" href="../assets/images/favicon/site.webmanifest" />
</head>

<body id="registerPage" role="main">
    <main>
        <div class="loginContainer" role="form">
            <div class="logoContainer">
                <a href="../index.php" aria-label="Retour à la page d'accueil">
                    <img src="../assets/images/logo.png" alt="Logo de CrunchTime">
                </a>
                <h1>CrunchTime</h1>
            </div>

            <!-- Affichage des différents messages d'erreur -->
            <?php if (!empty($message)): ?>
                <p class='errorMessage' role="alert"><?php echo $message; ?></p>
            <?php endif; ?>

            <!-- Affichage des différents messages de succès -->
            <?php if (!empty($successMessage)): ?>
                <p class='successMessage' role="status"><?php echo $successMessage; ?></p>
            <?php endif; ?>

            <form action="" method="post" class="loginForm" aria-label="Formulaire d'inscription">
                <div class="formInput">
                    <label for="firstname">Prénom :</label>
                    <input type="text" id="firstname" name="firstname" required aria-required="true" aria-label="Prénom">
                </div>
                <div class="formInput">
                    <label for="lastname">Nom :</label>
                    <input type="text" id="lastname" name="lastname" required aria-required="true" aria-label="Nom">
                </div>
                <div class="formInput">
                    <label for="username">Nom d'utilisateur :</label>
                    <input type="text" id="username" name="username" required aria-required="true" aria-label="Nom d'utilisateur">
                </div>
                <div class="formInput">
                    <label for="email">Email :</label>
                    <input type="email" id="email" name="email" required aria-required="true" aria-label="Adresse email">
                </div>
                <div class="formInput">
                    <label for="password">Mot de passe :</label>
                    <input type="password" id="password" name="password" required aria-required="true" aria-label="Mot de passe">
                </div>
                <div>
                    <button type="submit" class="submitButton" aria-label="S'inscrire">S'inscrire</button>
                </div>
            </form>
            <a href="login.php" class="registerLink" aria-label="Lien vers la page de connexion">Déjà un compte ? Se connecter</a>
        </div>
    </main>
</body>

</html>