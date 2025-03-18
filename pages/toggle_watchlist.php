<?php
session_start();

header("Content-Type: application/json");

// Si l'utilisateur n'est pas connecté...
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Veuillez vous connecter pour ajouter ou retirer de la watchlist.']);
    exit();
}

// Connexion à la base de données SQLite
$db = new SQLite3('../database/crunchtime.db');

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents("php://input"), true); // Décodage du JSON reçu
$media_id = isset($input['media_id']) ? intval($input['media_id']) : 0; // Sécurisation des données
$action = isset($input['action']) ? $input['action'] : '';

// Récupérer le type de média
$media_type = isset($input['media_type']) ? $input['media_type'] : 'movie';

// Vérifiez l'action (ajouter ou retirer)
if ($action === 'add') {
    // Vérifier si le média est déjà dans la watchlist
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
} elseif ($action === 'remove') {
    // Supprimer le média de la watchlist
    $stmt = $db->prepare("DELETE FROM watchlist WHERE user_id = :user_id AND media_id = :media_id");
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':media_id', $media_id, SQLITE3_INTEGER);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Le contenu a été retiré de votre watchlist.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors du retrait de la watchlist.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Action invalide.']);
}
?>