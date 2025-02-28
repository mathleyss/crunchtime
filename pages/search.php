<?php
session_start();

// Liste complète des genres
$genres = [
    "28" => "Action", "12" => "Aventure", "16" => "Animation", "35" => "Comédie",
    "80" => "Crime", "99" => "Documentaire", "18" => "Drame", "10751" => "Famille",
    "14" => "Fantaisie", "36" => "Histoire", "27" => "Horreur", "10402" => "Musique",
    "9648" => "Mystère", "10749" => "Romance", "878" => "Science-Fiction",
    "10770" => "Téléfilm", "53" => "Thriller", "10752" => "Guerre", "37" => "Western"
];

// Vérifier si une recherche a été faite
$movies = null;
if (isset($_GET['query']) || isset($_GET['genre']) || isset($_GET['year']) || isset($_GET['minRating'])) {
    $searchQuery = isset($_GET['query']) ? trim($_GET['query']) : '';
    $searchQuery = urlencode(strtolower($searchQuery));

    $genre = isset($_GET['genre']) ? intval($_GET['genre']) : '';
    $year = isset($_GET['year']) ? intval($_GET['year']) : '';
    $minRating = isset($_GET['minRating']) ? intval($_GET['minRating']) * 2 : '';

    $apiKey = "ad3586245e96a667f42a02c1b8708569";
    $apiUrl = "https://api.themoviedb.org/3/discover/movie?api_key=$apiKey&language=fr";
    
    if (!empty($searchQuery)) {
        $apiUrl = "https://api.themoviedb.org/3/search/movie?api_key=$apiKey&language=fr&query=$searchQuery";
    }
    if (!empty($genre)) {
        $apiUrl .= "&with_genres=$genre";
    }
    if (!empty($year)) {
        $apiUrl .= "&primary_release_year=$year";
    }
    if (!empty($minRating)) {
        $apiUrl .= "&vote_average.gte=$minRating";
    }
    
    $response = file_get_contents($apiUrl);
    $movies = json_decode($response, true);
}

// Connexion à SQLite
$db = new SQLite3('../crunchtime.db');
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("SELECT id, username, firstname, lastname, email, date FROM users WHERE id = :id");
    $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/crunchtime/assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <title>Résultats de recherche</title>
    <style>
        h1 {
            text-align: center;
            font-size: 2rem;
            margin-top: 20px;
            color: #333;
        }
        .filters {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 10px;
        }
        .filters select, .filters input, .filters button {
            padding: 10px;
            font-size: 1rem;
            border-radius: 5px;
            border: 1px solid #ccc;
            background: white;
        }
        .rating-filter {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        .rating-filter input {
            display: none;
        }
        .rating-filter label {
            cursor: pointer;
            font-size: 2rem;
            color: gray;
            transition: color 0.2s;
        }
        .rating-filter label:hover,
        .rating-filter label:hover ~ label,
        .rating-filter input:checked ~ label {
            color: gold;
        }
        .movie-genres span {
            background: #f1f1f1;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9em;
            margin-top: 5px;
            display: inline-block;
        }
        .star-rating {
            color: gold;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
<header>
    <nav class="menu menuOther">
        <div class="menuLeft">
            <a href="../index.php" class="logoAccueil"> <img src="/crunchtime/assets/images/logo.png" alt=""></a>
            <a href="../index.php">Accueil</a>
            <a href="swipe.php">CrunchSwipe</a>
        </div>
    </nav>
</header>

<main>
    <h1>Résultats de recherche</h1>
    <form class="filters" action="/crunchtime/pages/search.php" method="GET">
        <input type="text" name="query" placeholder="Rechercher..." class="searchInput">
        <select name="genre">
            <option value="">Tous les genres</option>
            <?php foreach ($genres as $id => $name): ?>
                <option value="<?= $id ?>"><?= $name ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="year" placeholder="Année" min="1900" max="<?= date('Y') ?>">
        <div class="rating-filter">
            <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="minRating" value="<?= $i ?>" id="rate-<?= $i ?>">
                <label for="rate-<?= $i ?>">★</label>
            <?php endfor; ?>
        </div>
        <button type="submit">Filtrer</button>
    </form>
    <?php if ($movies && isset($movies['results']) && count($movies['results']) > 0): ?>
        <div class="carousel-container">
            <div class="carousel">
                <?php foreach ($movies['results'] as $movie): ?>
                    <div class="movie-card">
                        <div class="movie-poster">
                            <a href="details.php?id=<?= $movie['id'] ?>&type=movie">
                                <img src="https://image.tmdb.org/t/p/w500<?= $movie['poster_path'] ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
                            </a>
                        </div>
                        <h4><?= htmlspecialchars($movie['title']) ?> (<?= substr($movie['release_date'], 0, 4) ?>)</h4>
                        <p class="movie-genres">
                            <?php foreach ($movie['genre_ids'] as $id): ?>
                                <span>#<?= $genres[$id] ?? "" ?></span>
                            <?php endforeach; ?>
                        </p>
                        <p class="star-rating">
                            <?php 
                            $rating = round($movie['vote_average'] / 2); 
                            for ($i = 0; $i < 5; $i++) {
                                echo $i < $rating ? "★" : "☆";
                            }
                            ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <p>Aucun résultat trouvé.</p>
    <?php endif; ?>
</main>
<script src="/crunchtime/assets/js/script.js"></script>
</body>
</html>
