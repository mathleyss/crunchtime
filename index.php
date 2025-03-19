<?php
session_start();

// Connexion à SQLite
$db = new SQLite3('database/crunchtime.db');
$user = null; // Initialiser la variable utilisateur

// Vérifier si l'utilisateur est connecté
if (isset($_SESSION['user_id'])) {
    // Préparer et exécuter la requête pour récupérer les infos de l'utilisateur
    $stmt = $db->prepare("SELECT id, username, firstname, lastname, email, date FROM users WHERE id = :id");
    $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();

    $user = $result->fetchArray(SQLITE3_ASSOC);
}

// Clé API de TMDB (remplace-la par la tienne)
$apiKey = 'ad3586245e96a667f42a02c1b8708569';
$cacheDirectory = 'cache/';

$urls = [
    'trending_movies' => "https://api.themoviedb.org/3/trending/movie/week?api_key=$apiKey&language=fr-FR",
    'trending_series' => "https://api.themoviedb.org/3/trending/tv/week?api_key=$apiKey&language=fr-FR",
    'action_movies' => "https://api.themoviedb.org/3/discover/movie?api_key=$apiKey&with_genres=28&language=fr-FR",
    'top_rated_series' => "https://api.themoviedb.org/3/tv/top_rated?api_key=$apiKey&language=fr-FR"
];



// MISE EN CACHE ----- EXPERIMENTAL
function getCachedApiResponse($url, $cacheDirectory, $cacheKey, $cacheDuration = 3600) {
    $cacheFile = $cacheDirectory . $cacheKey . '.json';

    // Vérifier si le fichier cache existe et s'il est encore valide
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheDuration) {
        return json_decode(file_get_contents($cacheFile), true);
    }

    // Faire la requête API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);

    // Stocker la réponse en cache
    file_put_contents($cacheFile, $response);

    return json_decode($response, true);
}

$movies = getCachedApiResponse($urls['trending_movies'], $cacheDirectory, 'trending_movies', 3600);
$series = getCachedApiResponse($urls['trending_series'], $cacheDirectory, 'trending_series', 3600);
$actionMovies = getCachedApiResponse($urls['action_movies'], $cacheDirectory, 'action_movies', 3600);
$topRatedSeries = getCachedApiResponse($urls['top_rated_series'], $cacheDirectory, 'top_rated_series', 3600);


// Récupération des médias dans la watchlist de l'utilisateur actuellement connecté
$watchlistMediaIds = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $db->prepare("SELECT media_id, media_type FROM watchlist WHERE user_id = :user_id");
    $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        // Créer une clé unique qui combine l'ID et le type
        $watchlistMediaIds[$row['media_id'] . '_' . $row['media_type']] = true;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <title>CrunchTime</title>

    <!-- Lien favicons -->
    <link rel="icon" type="image/png" href="assets/images/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon/favicon.svg" />
    <link rel="shortcut icon" href="assets/images/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="CrunchTime" />
    <link rel="manifest" href="assets/images/favicon/site.webmanifest" />

</head>

<body id="homePage">
    <header>
        <nav class="menu">
            <div class="menuLeft">
                <a href="index.php" class="logoAccueil"> <img src="assets/images/logo.png" alt=""></a>
                <a href="index.php" id="active">Accueil</a>
                <a href="pages/swipe.php">CrunchSwipe</a>
            </div>
            <!-- BARRE DE RECHERCHE -->
            <div class="searchBar">
                <form action="pages/search.php" method="GET">

                    <img src="assets/images/icon/search.svg" alt="Search">

                    <input type="text" name="query" placeholder="Rechercher..." class="searchInput" required>
                </form>
            </div>
            <div class="menuRight">

                <!-- Si un utilisateur est connecté, alors ... -->
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="profile">
                    <img src="https://doodleipsum.com/700/avatar-2?i=6197810111afde5fbb243bac8463665e" alt="Profile" class="profile-img">
                    <div class="dropdown-menu">
                        <img src="https://doodleipsum.com/700/avatar-2?i=6197810111afde5fbb243bac8463665e" alt="">
                        <p><?= htmlspecialchars($user['username']) ?></p>
                        <a href="pages/profile.php">Profil</a>
                        <a href="pages/watchlist.php">Ma watchlist</a>
                        <a href="pages/logout.php" id="logout">Déconnexion</a>
                    </div>
                </div>
                <!-- ... Sinon ... -->
                <?php else: ?>
                <a href="pages/login.php" class="btnLogin">
                    Connexion
                </a>
                <?php endif; ?>
            </div>
        </nav>
        <div class="headerContent">
            <h1>Crunchtime</h1>
            <p>Swipez, découvrez, partagez</p>
        </div>
    </header>

    <main>
    <!-----------------
            CARROUSEL DES
            FILMS TENDANCES
                    ----------------->
        <section class="filmsRecents">
            <div class="catFilmsRecents navTitleArrow">
                <h3 class="catTitle">Films tendances</h3>
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
                    <?php
    // Vérifier si la réponse contient des films
    if (isset($movies['results'])) {
        // Parcourir chaque film dans les résultats
        foreach ($movies['results'] as $movie) {
            $movieId = $movie['id']; // ID du film
            $movieTitle = htmlspecialchars($movie['title']); // Titre
            $moviePoster = "https://image.tmdb.org/t/p/w500" . $movie['poster_path']; // Affiche
            $releaseYear = date('Y', strtotime($movie['release_date'])); // Année de sortie
            $rating = htmlspecialchars($movie['vote_average']); // Note

            // Utilisation de la mise en cache pour récupérer les détails du film
            $cacheKey = "movie_details_$movieId";
            $movieDetails = getCachedApiResponse(
                "https://api.themoviedb.org/3/movie/$movieId?api_key=$apiKey&language=fr-FR&append_to_response=credits",
                $cacheDirectory,
                $cacheKey,
                3600
            );

            // Récupérer la durée du film
            $runtime = isset($movieDetails['runtime']) ? $movieDetails['runtime'] : 'N/A';
            $hours = floor($runtime / 60);
            $minutes = $runtime % 60;
            $formattedRuntime = $runtime !== 'N/A' ? "{$hours}h {$minutes}min" : 'Durée inconnue';

            // Récupérer les catégories
            $categories = [];
            if (isset($movieDetails['genres'])) {
                foreach ($movieDetails['genres'] as $genre) {
                    $categories[] = htmlspecialchars($genre['name']);
                }
            }
            $categoriesList = !empty($categories) ? implode(', ', $categories) : 'Aucune catégorie';

            // Récupérer le réalisateur
            $director = 'Inconnu';
            if (isset($movieDetails['credits']['crew'])) {
                foreach ($movieDetails['credits']['crew'] as $crewMember) {
                    if ($crewMember['job'] === 'Director') {
                        $director = htmlspecialchars($crewMember['name']);
                        break;
                    }
                }
            }
            ?>
                    <!-- Affichage HTML des films -->
                    <div class="movie-card">
                        <div class="movie-poster">
                            <a href="pages/details.php?id=<?= $movieId ?>&type=movie">
                                <?php if (!empty($movie['poster_path'])): ?>
                                    <img src="https://image.tmdb.org/t/p/w500<?= $movie['poster_path'] ?>" alt="<?= $movieTitle ?>" loading="lazy">
                                <?php else: ?>
                                    <img src="assets/images/placeholder.png" alt="<?= $movieTitle ?>" loading="lazy" class="placeholder-poster">
                                <?php endif; ?>
                            </a>
                        </div>
                        <h4><?= $movieTitle ?></h4>
                        <p><?= $releaseYear ?></p>

                        <!-- Gestion des ajouts dans la watchlist si l'utilisateur est connecté -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if (isset($watchlistMediaIds[$movieId . '_movie'])): ?>
                                <!-- Bouton qui valide la présence du média dans la watchlist -->
                                <div class="watchlist-added-indicator">
                                    <button class="success-check" disabled data-id="<?= htmlspecialchars($movieId); ?>">
                                        <!-- Icône de la coche -->
                                        <svg class="svgIconBtn checkIcon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"></path>
                                        </svg>
                                        <!-- Texte de suppression -->
                                    </button>
                                </div>
                            <?php else: ?>
                                <!-- Bouton pour ajouter le média (avec AJAX) -->
                                <div class="btnWatchlist btnWatchlistAdd">
                                    <button class="button toggle-watchlist" data-id="<?= htmlspecialchars($movieId); ?>" data-action="add"  data-type="movie">
                                        <!-- Icône "+" -->
                                        <svg class="svgIconBtn plusIcon" xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 14 14">
                                            <path fill="currentColor" fill-rule="evenodd" d="M8 1a1 1 0 0 0-2 0v5H1a1 1 0 0 0 0 2h5v5a1 1 0 1 0 2 0V8h5a1 1 0 1 0 0-2H8z" clip-rule="evenodd" />
                                        </svg>
                                        <!-- Texte d'ajout -->
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <!-- Fin de la gestion de la watchlist -->
                    </div>
                    <?php
        }
    } else {
        echo '<p>Aucun film tendance trouvé.</p>';
    }
    ?>
                </div>

            </div>
        </section>



    <!-----------------
            CARROUSEL DES
            FILMS D'ACTION
                    ----------------->
        <section class="filmsAction">
            <div class="catFilmsAction navTitleArrow">
            <h3 class="catTitle">Films d'action</h3>
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
                    <?php
    if (isset($actionMovies['results'])) {
        foreach ($actionMovies['results'] as $movie) {
            $movieId = $movie['id']; // ID du film
            $movieTitle = htmlspecialchars($movie['title']); // Titre
            $moviePoster = "https://image.tmdb.org/t/p/w500" . $movie['poster_path']; // Affiche
            $releaseYear = date('Y', strtotime($movie['release_date'])); // Année de sortie
            $rating = htmlspecialchars($movie['vote_average']); // Note

            // Récupération des détails du film avec cache
            $cacheKey = "movie_details_$movieId";
            $detailsUrl = "https://api.themoviedb.org/3/movie/$movieId?api_key=$apiKey&language=fr-FR&append_to_response=credits";
            $movieDetails = getCachedApiResponse($detailsUrl, $cacheDirectory, $cacheKey, 3600);

            // Récupérer la durée du film
            $runtime = isset($movieDetails['runtime']) ? $movieDetails['runtime'] : 'N/A';
            $hours = floor($runtime / 60);
            $minutes = $runtime % 60;
            $formattedRuntime = $runtime !== 'N/A' ? "{$hours}h {$minutes}min" : 'Durée inconnue';

            // Récupérer les catégories
            $categories = [];
            if (isset($movieDetails['genres'])) {
                foreach ($movieDetails['genres'] as $genre) {
                    $categories[] = htmlspecialchars($genre['name']);
                }
            }
            $categoriesList = !empty($categories) ? implode(', ', $categories) : 'Aucune catégorie';

            // Récupérer le réalisateur
            $director = 'Inconnu';
            if (isset($movieDetails['credits']['crew'])) {
                foreach ($movieDetails['credits']['crew'] as $crewMember) {
                    if ($crewMember['job'] === 'Director') {
                        $director = htmlspecialchars($crewMember['name']);
                        break;
                    }
                }
            }
            ?>
                    <div class="movie-card">
                        <div class="movie-poster">
                            <a href="pages/details.php?id=<?= $movieId ?>&type=movie">
                                <?php if (!empty($movie['poster_path'])): ?>
                                    <img src="https://image.tmdb.org/t/p/w500<?= $movie['poster_path'] ?>" alt="<?= $movieTitle ?>" loading="lazy">
                                <?php else: ?>
                                    <img src="assets/images/placeholder.png" alt="<?= $movieTitle ?>" loading="lazy" class="placeholder-poster">
                                <?php endif; ?>
                            </a>
                        </div>
                        <h4><?= $movieTitle ?></h4>
                        <p><?= $releaseYear ?></p>

                        <!-- Gestion des ajouts dans la watchlist si l'utilisateur est connecté -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if (isset($watchlistMediaIds[$movieId . '_movie'])): ?>
                                <!-- Bouton qui valide la présence du média dans la watchlist -->
                                <div class="watchlist-added-indicator">
                                    <button class="success-check" disabled data-id="<?= htmlspecialchars($movieId); ?>">
                                        <!-- Icône de la coche -->
                                        <svg class="svgIconBtn checkIcon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"></path>
                                        </svg>
                                        <!-- Texte de suppression -->
                                    </button>
                                </div>
                            <?php else: ?>
                                <!-- Bouton pour ajouter le média (avec AJAX) -->
                                <div class="btnWatchlist btnWatchlistAdd">
                                    <button class="button toggle-watchlist" data-id="<?= htmlspecialchars($movieId); ?>" data-action="add" data-type="movie">
                                        <!-- Icône "+" -->
                                        <svg class="svgIconBtn plusIcon" xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 14 14">
                                            <path fill="currentColor" fill-rule="evenodd" d="M8 1a1 1 0 0 0-2 0v5H1a1 1 0 0 0 0 2h5v5a1 1 0 1 0 2 0V8h5a1 1 0 1 0 0-2H8z" clip-rule="evenodd" />
                                        </svg>
                                        <!-- Texte d'ajout -->
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <!-- Fin de la gestion de la watchlist -->
                    </div>
                    <?php
        }
    } else {
        echo '<p>Aucun film d\'action trouvé.</p>';
    }
    ?>
                </div>

            </div>
        </section>

        <section class="cta">
            <div class="ctaText">
                <p> Vous hésitez ?</p>
                <p>Swipez !</p>
            </div>
            <a href="pages/swipe.php" class="btnRegister">Découvrez Crunchswipe</a>
        </section>


    <!-----------------
            CARROUSEL DES
            SERIES TENDANCES
                    ----------------->
        <section class="seriesTendances">
            <div class="catSeriesTendances navTitleArrow">
            <h3 class="catTitle">Séries tendances</h3>
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
                    <?php
    if (isset($series['results'])) {
        foreach ($series['results'] as $serie) {
            $serieId = $serie['id']; // ID de la série
            $serieTitle = htmlspecialchars($serie['name']); // Titre
            $seriePoster = "https://image.tmdb.org/t/p/w500" . $serie['poster_path']; // Affiche
            $releaseYear = date('Y', strtotime($serie['first_air_date'])); // Année de sortie
            $rating = htmlspecialchars($serie['vote_average']); // Note

            // Récupération des détails de la série avec cache
            $cacheKey = "serie_details_$serieId";
            $detailsUrl = "https://api.themoviedb.org/3/tv/$serieId?api_key=$apiKey&language=fr-FR&append_to_response=credits";
            $serieDetails = getCachedApiResponse($detailsUrl, $cacheDirectory, $cacheKey, 3600);

            // Récupérer le nombre de saisons
            $numberOfSeasons = isset($serieDetails['number_of_seasons']) ? $serieDetails['number_of_seasons'] : 'N/A';

            // Récupérer les catégories
            $categories = [];
            if (isset($serieDetails['genres'])) {
                foreach ($serieDetails['genres'] as $genre) {
                    $categories[] = htmlspecialchars($genre['name']);
                }
            }
            $categoriesList = !empty($categories) ? implode(', ', $categories) : 'Aucune catégorie';
            ?>
                    <div class="movie-card">
                        <div class="movie-poster">
                            <a href="pages/details.php?id=<?= $serieId ?>&type=tv">
                                <?php if (!empty($serie['poster_path'])): ?>
                                    <img src="https://image.tmdb.org/t/p/w500<?= $serie['poster_path'] ?>" alt="<?= $serieTitle ?>" loading="lazy">
                                <?php else: ?>
                                    <img src="assets/images/placeholder.jpg" alt="<?= $serieTitle ?>" loading="lazy" class="placeholder-poster">
                                <?php endif; ?>
                            </a>
                        </div>
                        <h4><?= $serieTitle ?></h4>
                        <p><?= $releaseYear ?></p>

                        <!-- Gestion des ajouts dans la watchlist si l'utilisateur est connecté -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if (isset($watchlistMediaIds[$serieId . '_tv'])): ?>
                                <!-- Bouton qui valide la présence du média dans la watchlist -->
                                <div class="watchlist-added-indicator">
                                    <button class="success-check" disabled data-id="<?= htmlspecialchars($serieId); ?>">
                                        <!-- Icône de la coche -->
                                        <svg class="svgIconBtn checkIcon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"></path>
                                        </svg>
                                        <!-- Texte de suppression -->
                                    </button>
                                </div>
                            <?php else: ?>
                                <!-- Bouton pour ajouter le média (avec AJAX) -->
                                <div class="btnWatchlist btnWatchlistAdd">
                                    <button class="button toggle-watchlist" data-id="<?= htmlspecialchars($serieId); ?>" data-action="add" data-type="tv">
                                        <!-- Icône "+" -->
                                        <svg class="svgIconBtn plusIcon" xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 14 14">
                                            <path fill="currentColor" fill-rule="evenodd" d="M8 1a1 1 0 0 0-2 0v5H1a1 1 0 0 0 0 2h5v5a1 1 0 1 0 2 0V8h5a1 1 0 1 0 0-2H8z" clip-rule="evenodd" />
                                        </svg>
                                        <!-- Texte d'ajout -->
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <!-- Fin de la gestion de la watchlist -->
                    </div>
                    <?php
        }
    } else {
        echo '<p>Aucune série tendance trouvée.</p>';
    }
    ?>
                </div>

            </div>
        </section>

    <!-----------------
            CARROUSEL DES
            SERIES LES MIEUX NOTEES 
                    ----------------->
        <section class="seriesTopRated">
            <div class="catSeriesTopRated navTitleArrow">
            <h3 class="catTitle">Séries les mieux notées</h3>
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
                    <?php
    if (isset($topRatedSeries['results'])) {
        foreach ($topRatedSeries['results'] as $serie) {
            $serieId = $serie['id']; // ID de la série
            $serieTitle = htmlspecialchars($serie['name']); // Titre
            $seriePoster = "https://image.tmdb.org/t/p/w500" . $serie['poster_path']; // Affiche
            $releaseYear = date('Y', strtotime($serie['first_air_date'])); // Année de sortie
            $rating = htmlspecialchars($serie['vote_average']); // Note

            // Récupération des détails de la série avec cache
            $cacheKey = "tv_details_$serieId";
            $detailsUrl = "https://api.themoviedb.org/3/tv/$serieId?api_key=$apiKey&language=fr-FR&append_to_response=credits";
            $serieDetails = getCachedApiResponse($detailsUrl, $cacheDirectory, $cacheKey, 3600);

            // Récupérer le nombre de saisons
            $numberOfSeasons = isset($serieDetails['number_of_seasons']) ? $serieDetails['number_of_seasons'] : 'N/A';

            // Récupérer les catégories
            $categories = [];
            if (isset($serieDetails['genres'])) {
                foreach ($serieDetails['genres'] as $genre) {
                    $categories[] = htmlspecialchars($genre['name']);
                }
            }
            $categoriesList = !empty($categories) ? implode(', ', $categories) : 'Aucune catégorie';
            ?>
                    <div class="movie-card">
                        <div class="movie-poster">
                            <a href="pages/details.php?id=<?= $serieId ?>&type=tv">
                                <?php if (!empty($serie['poster_path'])): ?>
                                    <img src="https://image.tmdb.org/t/p/w500<?= $serie['poster_path'] ?>" alt="<?= $serieTitle ?>" loading="lazy">
                                <?php else: ?>
                                    <img src="assets/images/placeholder.jpg" alt="<?= $serieTitle ?>" loading="lazy" class="placeholder-poster">
                                <?php endif; ?>
                            </a>
                        </div>
                        <h4><?= $serieTitle ?></h4>
                        <p><?= $releaseYear ?></p>

                        <!-- Gestion des ajouts dans la watchlist si l'utilisateur est connecté -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if (isset($watchlistMediaIds[$serieId . '_tv'])): ?>
                                <!-- Bouton qui valide la présence du média dans la watchlist -->
                                <div class="watchlist-added-indicator">
                                    <button class="success-check" disabled data-id="<?= htmlspecialchars($serieId); ?>">
                                        <!-- Icône de la coche -->
                                        <svg class="svgIconBtn checkIcon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"></path>
                                        </svg>
                                        <!-- Texte de suppression -->
                                    </button>
                                </div>
                            <?php else: ?>
                                <!-- Bouton pour ajouter le média (avec AJAX) -->
                                <div class="btnWatchlist btnWatchlistAdd">
                                    <button class="button toggle-watchlist" data-id="<?= htmlspecialchars($serieId); ?>" data-action="add" data-type="tv">
                                        <!-- Icône "+" -->
                                        <svg class="svgIconBtn plusIcon" xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 14 14">
                                            <path fill="currentColor" fill-rule="evenodd" d="M8 1a1 1 0 0 0-2 0v5H1a1 1 0 0 0 0 2h5v5a1 1 0 1 0 2 0V8h5a1 1 0 1 0 0-2H8z" clip-rule="evenodd" />
                                        </svg>
                                        <!-- Texte d'ajout -->
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <!-- Fin de la gestion de la watchlist -->
                    </div>
                    <?php
        }
    } else {
        echo '<p>Aucune série trouvée.</p>';
    }
    ?>
                </div>

            </div>
        </section>
    </main>

    <footer>


    </footer>
    <script src="assets/js/watchlist.js"></script>
    <script src="assets/js/script.js"></script>
</body>

</html>
