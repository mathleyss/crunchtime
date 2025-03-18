<?php
session_start();

// Affichage des message concernant l'ajout d'un média à la watchlist
if (isset($_SESSION['message'])) {
    echo "<div class='messageWatchlist'>{$_SESSION['message']}</div>";
    // Supprimez le message après l'affichage pour ne pas l'afficher à nouveau sur les rechargements de page
    unset($_SESSION['message']);
}

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
    $stmt = $db->prepare("SELECT media_id FROM watchlist WHERE user_id = :user_id");
    $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $watchlistMediaIds[] = $row['media_id'];
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
        <section class="filmsRecents">
            <div class="catFilmsRecents navTitleArrow">
                <h3>Films tendances</h3>
                <div class="buttonNav">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> 
                    <path d="M10.25 16.25C10.1493 16.2466 10.0503 16.2227 9.95921 16.1797C9.86807 16.1367 9.78668 16.0756 9.72001 16C9.57956 15.8594 9.50067 15.6688 9.50067 15.47C9.50067 15.2713 9.57956 15.0806 9.72001 14.94L12.72 11.94L9.72001 8.94002C9.66069 8.79601 9.64767 8.63711 9.68277 8.48536C9.71786 8.33361 9.79933 8.19656 9.91586 8.09322C10.0324 7.98988 10.1782 7.92538 10.3331 7.90868C10.4879 7.89198 10.6441 7.92391 10.78 8.00002L14.28 11.5C14.4205 11.6407 14.4994 11.8313 14.4994 12.03C14.4994 12.2288 14.4205 12.4194 14.28 12.56L10.78 16C10.7133 16.0756 10.6319 16.1367 10.5408 16.1797C10.4497 16.2227 10.3507 16.2466 10.25 16.25Z" fill="#000000"></path> </g>
                </svg>
                    <img src="assets/images/icon/arrowLeft.svg" alt="" class="prev recent-prev">
                    <img src="assets/images/icon/arrowRight.svg" class="next recent-next" alt="">
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
                                <img src="<?= $moviePoster ?>" alt="<?= $movieTitle ?>" loading="lazy">
                            </a>
                        </div>
                        <h4><?= $movieTitle ?></h4>
                        <p><?= $releaseYear ?></p>

                        <!-- Gestion de la watchlist si l'utilisateur est connecté -->
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (in_array($movieId, $watchlistMediaIds)): ?>
                        <!-- Formulaire pour supprimer le média -->
                        <form method="post" action="pages/suppression_watchlist_ancien.php" class="delete-form">
                            <input type="hidden" name="media_id" value="<?= htmlspecialchars($movieId); ?>">
                            <div class="btnWatchlist btnWatchlistDelIndex">
                                <button class="button" type="submit">
                                    <svg viewBox="0 0 448 512" class="svgIconBtn">
                                        <path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"></path>
                                    </svg>
                                </button>
                            </div>
                        </form>
                        <?php else: ?>
                        <!-- Formulaire pour ajouter le média -->
                        <form class="formulaireWatchlist" method="post" action="pages/ajout_watchlist_ancien.php">
                            <input type="hidden" name="media_id" value="<?= htmlspecialchars($movieId); ?>">
                            <div class="btnWatchlist btnWatchlistAdd">
                                <button class="button" type="submit">
                                    <svg class="svgIconBtn" xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 14 14">
                                        <path fill="currentColor" fill-rule="evenodd" d="M8 1a1 1 0 0 0-2 0v5H1a1 1 0 0 0 0 2h5v5a1 1 0 1 0 2 0V8h5a1 1 0 1 0 0-2H8z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                        <?php endif; ?>
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


        <!-- Carrousel des films d'action -->
        <section class="filmsAction">
            <div class="catFilmsAction navTitleArrow">
                <h3>Films d'action</h3>
                <div class="buttonNav">
                    <img src="assets/images/icon/arrowLeft.svg" alt="" class="prev action-prev">
                    <img src="assets/images/icon/arrowRight.svg" class="next action-next" alt="">
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
                                <img src="<?= $moviePoster ?>" alt="<?= $movieTitle ?>" loading="lazy">
                            </a>
                        </div>
                        <h4><?= $movieTitle ?></h4>
                        <p><?= $releaseYear ?></p>

                        <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (in_array($movieId, $watchlistMediaIds)): ?>
                        <form method="post" action="pages/suppression_watchlist.php">
                            <input type="hidden" name="media_id" value="<?= htmlspecialchars($movieId); ?>">
                            <div class="btnWatchlist btnWatchlistDelIndex">
                                <button class="button" type="submit">
                                    <svg viewBox="0 0 448 512" class="svgIconBtn">
                                        <path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"></path>
                                    </svg>
                                </button>
                            </div>
                        </form>
                        <?php else: ?>
                        <form class="formulaireWatchlist" method="post" action="pages/ajout_watchlist.php">
                            <input type="hidden" name="media_id" value="<?= htmlspecialchars($movieId); ?>">
                            <div class="btnWatchlist btnWatchlistAdd">
                                <button class="button" type="submit">
                                    <svg class="svgIconBtn" xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 14 14">
                                        <path fill="currentColor" fill-rule="evenodd" d="M8 1a1 1 0 0 0-2 0v5H1a1 1 0 0 0 0 2h5v5a1 1 0 1 0 2 0V8h5a1 1 0 1 0 0-2H8z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                        <?php endif; ?>
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


        <!-- Carrousel des séries tendances -->
        <section class="seriesTendances">
            <div class="catSeriesTendances navTitleArrow">
                <h3>Séries tendances</h3>
                <div class="buttonNav">
                    <img src="assets/images/icon/arrowLeft.svg" alt="" class="prev series-prev">
                    <img src="assets/images/icon/arrowRight.svg" class="next series-next" alt="">
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
                                <img src="<?= $seriePoster ?>" alt="<?= $serieTitle ?>" loading="lazy">
                            </a>
                        </div>
                        <h4><?= $serieTitle ?></h4>
                        <p><?= $releaseYear ?></p>

                        <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (in_array($serieId, $watchlistMediaIds)): ?>
                        <form method="post" action="pages/suppression_watchlist.php">
                            <input type="hidden" name="media_id" value="<?= htmlspecialchars($serieId); ?>">
                            <div class="btnWatchlist btnWatchlistDelIndex">
                                <button class="button" type="submit">
                                    <svg viewBox="0 0 448 512" class="svgIconBtn">
                                        <path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"></path>
                                    </svg>
                                </button>
                            </div>
                        </form>
                        <?php else: ?>
                        <form class="formulaireWatchlist" method="post" action="pages/ajout_watchlist.php">
                            <input type="hidden" name="media_id" value="<?= htmlspecialchars($serieId); ?>">
                            <div class="btnWatchlist btnWatchlistAdd">
                                <button class="button" type="submit">
                                    <svg class="svgIconBtn" xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 14 14">
                                        <path fill="currentColor" fill-rule="evenodd" d="M8 1a1 1 0 0 0-2 0v5H1a1 1 0 0 0 0 2h5v5a1 1 0 1 0 2 0V8h5a1 1 0 1 0 0-2H8z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                        <?php endif; ?>
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

        <!-- Carrousel des séries les mieux notées -->
        <section class="seriesTopRated">
            <div class="catSeriesTopRated navTitleArrow">
                <h3>Séries les mieux notées</h3>
                <div class="buttonNav">
                    <img src="assets/images/icon/arrowLeft.svg" alt="" class="prev top-rated-prev">
                    <img src="assets/images/icon/arrowRight.svg" class="next top-rated-next" alt="">
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
                                <img src="<?= $seriePoster ?>" alt="<?= $serieTitle ?>" loading="lazy">
                            </a>
                        </div>
                        <h4><?= $serieTitle ?></h4>
                        <p><?= $releaseYear ?></p>

                        <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (in_array($serieId, $watchlistMediaIds)): ?>
                        <form method="post" action="pages/suppression_watchlist.php">
                            <input type="hidden" name="media_id" value="<?= htmlspecialchars($serieId); ?>">
                            <div class="btnWatchlist btnWatchlistDelIndex">
                                <button class="button" type="submit">
                                    <svg viewBox="0 0 448 512" class="svgIconBtn">
                                        <path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"></path>
                                    </svg>
                                </button>
                            </div>
                        </form>
                        <?php else: ?>
                        <form class="formulaireWatchlist" method="post" action="pages/ajout_watchlist.php">
                            <input type="hidden" name="media_id" value="<?= htmlspecialchars($serieId); ?>">
                            <div class="btnWatchlist btnWatchlistAdd">
                                <button class="button" type="submit">
                                    <svg class="svgIconBtn" xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 14 14">
                                        <path fill="currentColor" fill-rule="evenodd" d="M8 1a1 1 0 0 0-2 0v5H1a1 1 0 0 0 0 2h5v5a1 1 0 1 0 2 0V8h5a1 1 0 1 0 0-2H8z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                        <?php endif; ?>
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
    <script src="assets/js/script.js"></script>
</body>

</html>
