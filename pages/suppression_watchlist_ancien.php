<?php
session_start(); // Démarrage de la session

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$media_id = intval($_POST['media_id']); // Sécurisation des données

// Connexion à SQLite
$db = new SQLite3('../database/crunchtime.db');

try {
    // Supprimer le média de la watchlist
    $stmt = $db->prepare("DELETE FROM watchlist WHERE user_id = :user_id AND media_id = :media_id");
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':media_id', $media_id, SQLITE3_INTEGER);
    $stmt->execute();

    // Rediriger vers la page de la watchlist
    header("Location: watchlist.php");
    exit();
} catch (Exception $e) {
    die("Erreur lors de la suppression : " . $e->getMessage());
}