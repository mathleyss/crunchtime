<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();

header("Content-Type: application/json");

function exception_error_handler($severity, $message, $file, $line) {
    error_log("PHP Error in $file:$line - $message");
    throw new ErrorException($message, 0, $severity, $file, $line);
}
set_error_handler("exception_error_handler");

try {
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

    error_log("User ID: $user_id, Media ID: $media_id, Media Type: $media_type"); // Débogage

    // Vérifiez si le média est déjà dans la watchlist avec le même type
    $stmt = $db->prepare("SELECT * FROM watchlist WHERE user_id = :user_id AND media_id = :media_id AND media_type = :media_type");
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':media_id', $media_id, SQLITE3_INTEGER);
    $stmt->bindValue(':media_type', $media_type, SQLITE3_TEXT);
    $result = $stmt->execute();

    if ($result->fetchArray()) {
        error_log("Le média est déjà dans la watchlist"); // Débogage
        echo json_encode(['success' => false, 'message' => 'Ce contenu est déjà dans votre watchlist.']);
    } else {
        // Ajouter le média à la watchlist avec son type
        $stmt = $db->prepare("INSERT INTO watchlist (user_id, media_id, media_type, added_at) VALUES (:user_id, :media_id, :media_type, CURRENT_TIMESTAMP)");
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(':media_id', $media_id, SQLITE3_INTEGER);
        $stmt->bindValue(':media_type', $media_type, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            error_log("Média ajouté avec succès"); // Débogage
            echo json_encode(['success' => true, 'message' => 'Le contenu a été ajouté à votre watchlist.']);
        } else {
            $error = $db->lastErrorMsg();
            error_log("Erreur lors de l'ajout du média: " . $error); // Débogage
            
            // Si c'est une erreur de contrainte unique, donner un message plus clair
            if (strpos($error, 'UNIQUE constraint failed') !== false) {
                echo json_encode(['success' => false, 'message' => 'Ce contenu est déjà dans votre watchlist.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout à la watchlist: ' . $error]);
            }
        }
    }
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>