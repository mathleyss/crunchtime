<?php
// Script pour mettre à jour la structure de la table watchlist

// Connexion à la base de données
$db = new SQLite3('database/crunchtime.db');

// Désactiver les contraintes d'intégrité référentielle temporairement
$db->exec('PRAGMA foreign_keys = OFF');

// Commencer une transaction
$db->exec('BEGIN TRANSACTION');

try {
    // 1. Créer une nouvelle table avec la structure souhaitée
    $db->exec('CREATE TABLE watchlist_new (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        media_id INTEGER NOT NULL,
        media_type TEXT NOT NULL DEFAULT "movie",
        added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, media_id, media_type),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )');

    // 2. Copier les données de l'ancienne table vers la nouvelle
    $db->exec('INSERT INTO watchlist_new (user_id, media_id, media_type, added_at)
               SELECT user_id, media_id, 
                      CASE WHEN media_type IS NULL THEN "movie" ELSE media_type END,
                      CASE WHEN added_at IS NULL THEN CURRENT_TIMESTAMP ELSE added_at END
               FROM watchlist');

    // 3. Supprimer l'ancienne table
    $db->exec('DROP TABLE watchlist');

    // 4. Renommer la nouvelle table
    $db->exec('ALTER TABLE watchlist_new RENAME TO watchlist');

    // Valider la transaction
    $db->exec('COMMIT');
    
    echo "La structure de la table watchlist a été mise à jour avec succès!";
} catch (Exception $e) {
    // En cas d'erreur, annuler la transaction
    $db->exec('ROLLBACK');
    echo "Erreur lors de la mise à jour de la table: " . $e->getMessage();
}

// Réactiver les contraintes d'intégrité référentielle
$db->exec('PRAGMA foreign_keys = ON');
?>