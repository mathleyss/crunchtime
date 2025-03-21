<?php
session_start();
$db = new SQLite3('../database/crunchtime.db');
$apiKey = 'ad3586245e96a667f42a02c1b8708569';

$mediaDetails = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("SELECT media_id, media_type, added_at FROM watchlist WHERE user_id = :user_id ORDER BY added_at DESC");
    $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();

    $mediaList = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $mediaList[] = [
            'id' => $row['media_id'],
            'type' => $row['media_type'],
            'added_at' => $row['added_at']
        ];
    }

    foreach ($mediaList as $media) {
        $media_id = $media['id'];
        $media_type = $media['type'];

        $apiUrl = "https://api.themoviedb.org/3/{$media_type}/{$media_id}?api_key={$apiKey}&language=fr-FR";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $mediaDetails[] = json_decode($response, true);
    }
}

if (empty($mediaDetails)) {
    echo '<p class="erreurWatchlist">Vous n\'avez actuellement aucun film ou série dans votre watchlist.</p>';
} else {
    foreach ($mediaDetails as $media) {
        ?>
        <div class="movie-card">
            <div class="movie-poster">
                <a href="details.php?id=<?= $media['id'] ?>">
                    <?php if (!empty($media['poster_path'])): ?>
                        <img src="https://image.tmdb.org/t/p/w500<?= $media['poster_path'] ?>" alt="<?= htmlspecialchars($media['title'] ?? $media['name']) ?>">
                    <?php else: ?>
                        <img src="../assets/images/placeholder.png" alt="<?= htmlspecialchars($media['title'] ?? $media['name']) ?>" class="placeholder-poster">
                    <?php endif; ?>
                </a>
            </div>
            <div class="media-title-container">
                <h3><?= htmlspecialchars($media['title'] ?? $media['name']) ?></h3>
            </div>
        </div>
        <?php
    }
}
?>