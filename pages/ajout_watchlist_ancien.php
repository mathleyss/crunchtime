<?php
session_start();

// Si l'utilisateur n'est pas connecté...
if (!isset($_SESSION['user_id'])) {
    // ... alors redirection sur la page de connexion
    header("Location: login.php");
    exit(); // arrêt script
}

// Connexion à la base de données SQLite
$db = new SQLite3('../database/crunchtime.db');

$user_id = $_SESSION['user_id'];
$media_id = isset($_POST['media_id']) ? intval($_POST['media_id']) : 0; // Sécurisation des données

// Vérifiez si le média est déjà dans la watchlist
$stmt = $db->prepare("SELECT * FROM watchlist WHERE user_id = :user_id AND media_id = :media_id");
$stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
$stmt->bindValue(':media_id', $media_id, SQLITE3_INTEGER);
$result = $stmt->execute();

if ($result->fetchArray()) {
    $_SESSION['message'] = "Ce contenu est déjà dans votre watchlist.";
} else {
    // Ajoutez le média à la watchlist
    $stmt = $db->prepare("INSERT INTO watchlist (user_id, media_id) VALUES (:user_id, :media_id)");
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':media_id', $media_id, SQLITE3_INTEGER);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Le contenu a été ajouté à votre watchlist.";
    } else {
        $_SESSION['message'] = "Erreur lors de l'ajout à la watchlist.";
    }
}

// Redirection vers la page d'accueil
header("Location: ../index.php");
exit();
?>