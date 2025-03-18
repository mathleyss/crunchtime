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
$apiKey = "ad3586245e96a667f42a02c1b8708569";

if (isset($_GET['query']) || isset($_GET['genre']) || isset($_GET['year'])) {
    $searchQuery = isset($_GET['query']) ? trim($_GET['query']) : '';
    $searchQuery = urlencode(strtolower($searchQuery));

    $genre = isset($_GET['genre']) ? $_GET['genre'] : '';
    $year = isset($_GET['year']) ? intval($_GET['year']) : '';

    if (!empty($searchQuery)) {
        // Si une recherche par mot-clé est faite, utiliser search/movie
        $apiUrl = "https://api.themoviedb.org/3/search/movie?api_key=$apiKey&language=fr&query=$searchQuery";
    } else {
        // Sinon, utiliser discover/movie pour les filtres de genre et d'année
        $apiUrl = "https://api.themoviedb.org/3/discover/movie?api_key=$apiKey&language=fr";

        if (!empty($genre) && array_key_exists($genre, $genres)) {
            $apiUrl .= "&with_genres=" . urlencode($genre);
        }
        if (!empty($year)) {
            $apiUrl .= "&primary_release_year=$year";
        }
    }

    $response = file_get_contents($apiUrl);
    $movies = json_decode($response, true);

    // Si un genre est sélectionné et une recherche par mot-clé est faite
    if (!empty($searchQuery) && !empty($genre)) {
        $filteredMovies = [];
        foreach ($movies['results'] as $movie) {
            if (in_array($genre, $movie['genre_ids'])) {
                $filteredMovies[] = $movie;
            }
        }
        $movies['results'] = $filteredMovies;
    }
}

// Connexion à SQLite
$db = new SQLite3('../database/crunchtime.db');
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
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <title>Recherche</title>
</head>
<body>
<header>
        <nav class="menu menuOther">
            <div class="menuLeft">
                <a href="../index.php" class="logoAccueil"> <img src="../assets/images/logo.png" alt=""></a>
                <a href="../index.php">Accueil</a>
                <a href="swipe.php">CrunchSwipe</a>
            </div>

            <div class="menuRight">

                <!-- Si un utilisateur est connecté, alors ... -->
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="profile">
                    <img src="https://doodleipsum.com/700/avatar-2?i=6197810111afde5fbb243bac8463665e" alt="Profile" class="profile-img">
                    <div class="dropdown-menu">
                        <img src="https://doodleipsum.com/700/avatar-2?i=6197810111afde5fbb243bac8463665e" alt="">
                        <p><?= htmlspecialchars($user['username']) ?></p>
                        <a href="profile.php">Profil</a>
                        <a href="watchlist.php">Ma watchlist</a>
                        <a href="logout.php" id="logout">Déconnexion</a>
                    </div>
                </div>
                <!-- ... Sinon ... -->
                <?php else: ?>
                <a href="login.php" class="btnLogin">
                    Connexion
                </a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

<main class="searchPage">
    <h1 class="titleSearch">Résultats de recherche</h1>
    <form class="filters" action="search.php" method="GET">
        <input type="text" name="query" placeholder="Rechercher..." class="searchInput">
        <select name="genre">
            <option value="">Tous les genres</option>
            <?php foreach ($genres as $id => $name): ?>
                <option value="<?= $id ?>"><?= $name ?></option>
            <?php endforeach; ?>
        </select>
        <input class="yearInput" type="number" name="year" placeholder="Année" min="1900" max="<?= date('Y') ?>">
        <button type="submit" class="searchPageBtn">Rechercher</button>
    </form>
    <?php if ($movies && isset($movies['results']) && count($movies['results']) > 0): ?>
        <div class="buttonNavSearch">
        <div class="buttonNav">
        <svg class="prev" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier">
                    <path d="M13.75 16.25C13.6515 16.2505 13.5538 16.2313 13.4628 16.1935C13.3718 16.1557 13.2893 16.1001 13.22 16.03L9.72001 12.53C9.57956 12.3894 9.50067 12.1988 9.50067 12C9.50067 11.8013 9.57956 11.6107 9.72001 11.47L13.22 8.00003C13.361 7.90864 13.5285 7.86722 13.6958 7.88241C13.8631 7.89759 14.0205 7.96851 14.1427 8.08379C14.2649 8.19907 14.3448 8.35203 14.3697 8.51817C14.3946 8.68431 14.363 8.85399 14.28 9.00003L11.28 12L14.28 15C14.4205 15.1407 14.4994 15.3313 14.4994 15.53C14.4994 15.7288 14.4205 15.9194 14.28 16.06C14.1353 16.1907 13.9448 16.259 13.75 16.25Z" fill="#000000"></path> </g>
                </svg>
                <svg class="next" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> 
                    <path d="M10.25 16.25C10.1493 16.2466 10.0503 16.2227 9.95921 16.1797C9.86807 16.1367 9.78668 16.0756 9.72001 16C9.57956 15.8594 9.50067 15.6688 9.50067 15.47C9.50067 15.2713 9.57956 15.0806 9.72001 14.94L12.72 11.94L9.72001 8.94002C9.66069 8.79601 9.64767 8.63711 9.68277 8.48536C9.71786 8.33361 9.79933 8.19656 9.91586 8.09322C10.0324 7.98988 10.1782 7.92538 10.3331 7.90868C10.4879 7.89198 10.6441 7.92391 10.78 8.00002L14.28 11.5C14.4205 11.6407 14.4994 11.8313 14.4994 12.03C14.4994 12.2288 14.4205 12.4194 14.28 12.56L10.78 16C10.7133 16.0756 10.6319 16.1367 10.5408 16.1797C10.4497 16.2227 10.3507 16.2466 10.25 16.25Z" fill="#000000"></path> </g>
                </svg>
                </div>
                </div>

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
                                <span><?= $genres[$id] ?? "" ?></span>
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
        <p class="noResultsMessage">Aucun résultat trouvé. <br>Vous pouvez faire une nouvelle recherche.</p>
    <?php endif; ?>
</main>
<script src="../assets/js/script.js"></script>
</body>
</html>
