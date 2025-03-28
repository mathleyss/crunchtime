<?php
session_start();

header("Content-Type: application/json");

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Veuillez vous connecter pour supprimer de la watchlist.']);
    exit();
}

// Vérification si l'ID du média est passé via POST
if (!isset($_POST['media_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID du média manquant.']);
    exit();
}

$db = new SQLite3('../database/crunchtime.db');
$user_id = $_SESSION['user_id'];
$media_id = intval($_POST['media_id']); // Sécurisation des données
$media_type = isset($_POST['media_type']) ? $_POST['media_type'] : 'movie'; // Type par défaut: movie

// Supprimer le média de la watchlist
$stmt = $db->prepare("DELETE FROM watchlist WHERE user_id = :user_id AND media_id = :media_id AND media_type = :media_type");
$stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
$stmt->bindValue(':media_id', $media_id, SQLITE3_INTEGER);
$stmt->bindValue(':media_type', $media_type, SQLITE3_TEXT);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Le contenu a été retiré de votre watchlist.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de la watchlist.']);
}
?>