<?php
session_start();

header("Content-Type: application/json");

// Si l'utilisateur n'est pas connecté...
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Veuillez vous connecter pour ajouter à la watchlist.']);
    exit();
}

// Connexion à la base de données SQLite
$db = new SQLite3('../database/crunchtime.db');

$user_id = $_SESSION['user_id'];
$media_id = isset($_POST['media_id']) ? intval($_POST['media_id']) : 0; // Sécurisation des données

// Récupérer le type de média
$media_type = isset($_POST['media_type']) ? $_POST['media_type'] : 'movie';

// Vérifiez si le média est déjà dans la watchlist
$stmt = $db->prepare("SELECT * FROM watchlist WHERE user_id = :user_id AND media_id = :media_id");
$stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
$stmt->bindValue(':media_id', $media_id, SQLITE3_INTEGER);
$result = $stmt->execute();

if ($result->fetchArray()) {
    echo json_encode(['success' => false, 'message' => 'Ce contenu est déjà dans votre watchlist.']);
} else {
    // Ajouter le média à la watchlist avec son type
    $stmt = $db->prepare("INSERT INTO watchlist (user_id, media_id, media_type, added_at) VALUES (:user_id, :media_id, :media_type, CURRENT_TIMESTAMP)");
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':media_id', $media_id, SQLITE3_INTEGER);
    $stmt->bindValue(':media_type', $media_type, SQLITE3_TEXT);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Le contenu a été ajouté à votre watchlist.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout à la watchlist.']);
    }
}
?>