<?php
session_start();


// Connexion à SQLite
$db = new SQLite3('crunchtime.db');

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

// URL de l'API pour les films tendances sur la semaine
$apiUrl = "https://api.themoviedb.org/3/trending/movie/week?api_key=$apiKey&language=fr-FR";


// Initialiser cURL pour récupérer les films tendances
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

// Exécuter la requête et obtenir la réponse JSON
$response = curl_exec($ch);
curl_close($ch);

// Décoder la réponse JSON en tableau associatif PHP
$movies = json_decode($response, true);

// URL de l'API pour les séries tendances sur la semaine
$apiUrlSeries = "https://api.themoviedb.org/3/trending/tv/week?api_key=$apiKey&language=fr-FR";

// Initialiser cURL pour récupérer les séries tendances
$chSeries = curl_init();
curl_setopt($chSeries, CURLOPT_URL, $apiUrlSeries);
curl_setopt($chSeries, CURLOPT_RETURNTRANSFER, 1);

// Exécuter la requête et obtenir la réponse JSON
$responseSeries = curl_exec($chSeries);
curl_close($chSeries);

// Décoder la réponse JSON en tableau associatif PHP
$series = json_decode($responseSeries, true);

// URL de l'API pour les films d'action
$apiUrlActionMovies = "https://api.themoviedb.org/3/discover/movie?api_key=$apiKey&with_genres=28&language=fr-FR";

// Initialiser cURL pour récupérer les films d'action
$chActionMovies = curl_init();
curl_setopt($chActionMovies, CURLOPT_URL, $apiUrlActionMovies);
curl_setopt($chActionMovies, CURLOPT_RETURNTRANSFER, 1);

// Exécuter la requête et obtenir la réponse JSON
$responseActionMovies = curl_exec($chActionMovies);
curl_close($chActionMovies);

// Décoder la réponse JSON en tableau associatif PHP
$actionMovies = json_decode($responseActionMovies, true);

// URL de l'API pour les séries les mieux notées
$apiUrlTopRatedSeries = "https://api.themoviedb.org/3/tv/top_rated?api_key=$apiKey&language=fr-FR";

// Initialiser cURL pour récupérer les séries les mieux notées
$chTopRatedSeries = curl_init();
curl_setopt($chTopRatedSeries, CURLOPT_URL, $apiUrlTopRatedSeries);
curl_setopt($chTopRatedSeries, CURLOPT_RETURNTRANSFER, 1);

// Exécuter la requête et obtenir la réponse JSON
$responseTopRatedSeries = curl_exec($chTopRatedSeries);
curl_close($chTopRatedSeries);

// Décoder la réponse JSON en tableau associatif PHP
$topRatedSeries = json_decode($responseTopRatedSeries, true);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap"
        rel="stylesheet">
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
            <!-- BARRE DE RECHERCHE À REFAIRE ET EN CSS AUSSI -->
            <div class="searchBar">
                <form action="/crunchtime/pages/search.php" method="GET">

                    <img src="assets/images/icon/search.svg" alt="Search">

                    <input type="text" name="query" placeholder="Rechercher..." class="searchInput" required>
                </form>
            </div>
            <div class="menuRight">

                <!-- Si un utilisateur est connecté, alors ... -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="profile">
                        <img src="https://doodleipsum.com/700/avatar-2?i=6197810111afde5fbb243bac8463665e" alt="Profile"
                            class="profile-img">
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
                <h3>Films sorti récemments</h3>
                <div class="buttonNav">
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
                    
                            // Récupération des détails du film (réalisateur, durée, catégories)
                            $detailsUrl = "https://api.themoviedb.org/3/movie/$movieId?api_key=$apiKey&language=fr-FR&append_to_response=credits";
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $detailsUrl);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            $detailsResponse = curl_exec($ch);
                            curl_close($ch);

                            $movieDetails = json_decode($detailsResponse, true);

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
                                    <a href="pages/details.php?id=<?= $movieId ?>&type=movie"><img src="<?= $moviePoster ?>"
                                            alt="<?= $movieTitle ?>"></a>
                                    <div class="more-info">
                                        <p> <?= $director ?></p>
                                        <p> <?= $categoriesList ?></p>
                                        <p> <?= $formattedRuntime ?></p>
                                        <p> <?= $rating ?>/10</p>
                                    </div>
                                    <button class="toggle-info">
                                        <img src="assets/images/icon/arrowUp.svg" alt="">
                                    </button>
                                </div>
                                <h4><?= $movieTitle ?></h4>
                                <p><?= $releaseYear ?></p>
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
                    
                            // Récupération des détails du film (réalisateur, durée, catégories)
                            $detailsUrl = "https://api.themoviedb.org/3/movie/$movieId?api_key=$apiKey&language=fr-FR&append_to_response=credits";
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $detailsUrl);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            $detailsResponse = curl_exec($ch);
                            curl_close($ch);

                            $movieDetails = json_decode($detailsResponse, true);

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
                                    <a href="pages/details.php?id=<?= $movieId ?>&type=movie"><img src="<?= $moviePoster ?>"
                                            alt="<?= $movieTitle ?>"></a>
                                    <div class="more-info">
                                        <p> <?= $director ?></p>
                                        <p> <?= $categoriesList ?></p>
                                        <p><?= $formattedRuntime ?></p>
                                        <p><?= $rating ?>/10</p>
                                    </div>
                                    <button class="toggle-info">
                                        <img src="assets/images/icon/arrowUp.svg" alt="">
                                    </button>
                                </div>
                                <h4><?= $movieTitle ?></h4>
                                <p><?= $releaseYear ?></p>
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
                    // Vérifier si la réponse contient des séries
                    if (isset($series['results'])) {
                        // Parcourir chaque série dans les résultats
                        foreach ($series['results'] as $serie) {
                            $serieId = $serie['id']; // ID de la série
                            $serieTitle = htmlspecialchars($serie['name']); // Titre
                            $seriePoster = "https://image.tmdb.org/t/p/w500" . $serie['poster_path']; // Affiche
                            $releaseYear = date('Y', strtotime($serie['first_air_date'])); // Année de sortie
                            $rating = htmlspecialchars($serie['vote_average']); // Note
                    
                            // Récupération des détails de la série (nombre de saisons, catégories)
                            $detailsUrl = "https://api.themoviedb.org/3/tv/$serieId?api_key=$apiKey&language=fr-FR&append_to_response=credits";
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $detailsUrl);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            $detailsResponse = curl_exec($ch);
                            curl_close($ch);

                            $serieDetails = json_decode($detailsResponse, true);

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
                            <!-- Affichage HTML des séries -->
                            <div class="movie-card">
                                <div class="movie-poster">
                                    <a href="pages/details.php?id=<?= $serieId ?>&type=tv"><img src="<?= $seriePoster ?>"
                                            alt="<?= $serieTitle ?>"></a>
                                    <div class="more-info">
                                        <p><?= $numberOfSeasons ?>         <?= $numberOfSeasons > 1 ? 'Saisons' : 'Saison' ?></p>
                                        <p><?= $categoriesList ?></p>
                                        <p><?= $rating ?>/10</p>
                                    </div>
                                    <button class="toggle-info">
                                        <img src="assets/images/icon/arrowUp.svg" alt="">
                                    </button>
                                </div>
                                <h4><?= $serieTitle ?></h4>
                                <p><?= $releaseYear ?></p>
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
                    
                            // Récupération des détails de la série (nombre de saisons, catégories)
                            $detailsUrl = "https://api.themoviedb.org/3/tv/$serieId?api_key=$apiKey&language=fr-FR&append_to_response=credits";
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $detailsUrl);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            $detailsResponse = curl_exec($ch);
                            curl_close($ch);

                            $serieDetails = json_decode($detailsResponse, true);

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
                            <!-- Affichage HTML des séries -->
                            <div class="movie-card">
                                <div class="movie-poster">
                                    <a href="pages/details.php?id=<?= $serieId ?>&type=tv"><img src="<?= $seriePoster ?>"
                                            alt="<?= $serieTitle ?>"></a>

                                    <div class="more-info">
                                        <p><?= $numberOfSeasons ?><?= $numberOfSeasons > 1 ? 'Saisons' : 'Saison' ?></p>
                                        <p><?= $categoriesList ?></p>
                                        <p><?= $rating ?>/10</p>
                                    </div>
                                    <button class="toggle-info">
                                        <img src="assets/images/icon/arrowUp.svg" alt="">
                                    </button>
                                </div>
                                <h4><?= $serieTitle ?></h4>
                                <p><?= $releaseYear ?></p>
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