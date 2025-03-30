<?php
session_start();

// Vérifie si l'utilisateur est connecté, sinon redirige vers la page de connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Connexion à la base de données SQLite
$db = new SQLite3('../database/crunchtime.db');

// Clé API pour interagir avec l'API de TMDB
$apiKey = 'ad3586245e96a667f42a02c1b8708569';

// Récupère les informations de l'utilisateur connecté
$stmt = $db->prepare("SELECT id, username, firstname, lastname, email, date FROM users WHERE id = :id");
$stmt->bindValue(':id', $user_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$user = $result->fetchArray(SQLITE3_ASSOC);

// Récupère les médias présents dans la watchlist de l'utilisateur
$stmt = $db->prepare("SELECT media_id, media_type, added_at FROM watchlist WHERE user_id = :user_id ORDER BY added_at DESC");
$stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
$result = $stmt->execute();

// Stocke les informations de base des médias (ID, type, date d'ajout)
$mediaList = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $mediaList[] = [
        'id' => $row['media_id'],
        'type' => $row['media_type'],
        'added_at' => $row['added_at']
    ];
}

// Tableau pour stocker les détails complets des médias récupérés via l'API
$mediaDetails = [];

// Parcourt chaque média pour récupérer ses détails via l'API TMDB
foreach ($mediaList as $media) {
    $media_id = $media['id'];
    $media_type = $media['type'];

    // Construit l'URL de l'API en fonction du type de média (film ou série)
    $apiUrl = "https://api.themoviedb.org/3/{$media_type}/{$media_id}?api_key={$apiKey}&language=fr-FR&append_to_response=credits";

    // Effectue une requête cURL pour récupérer les données du média
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);

    // Décode la réponse JSON en tableau PHP
    $details = json_decode($response, true);

    // Si le média est un film, récupère les informations spécifiques aux films
    if ($media_type === 'movie' && isset($details['title'])) {
        $mediaDetails[] = [
            'id' => $media_id,
            'title' => $details['title'],
            'poster_path' => $details['poster_path'],
            'release_date' => $details['release_date'],
            'vote_average' => $details['vote_average'],
            'overview' => $details['overview'],
            'media_type' => 'movie',
            'director' => getDirector($details), // Appelle une fonction pour récupérer le réalisateur
            'runtime' => $details['runtime'],
            'added_at' => $media['added_at'],
            'genres' => implode(', ', array_map(function ($genre) {
                return $genre['name']; }, $details['genres'] ?? [])) // Formate les genres en chaîne de caractères
        ];

    // Si le média est une série, récupère les informations spécifiques aux séries
    } elseif ($media_type === 'tv' && isset($details['name'])) {
        $mediaDetails[] = [
            'id' => $media_id,
            'title' => $details['name'],
            'poster_path' => $details['poster_path'],
            'release_date' => $details['first_air_date'],
            'vote_average' => $details['vote_average'],
            'overview' => $details['overview'],
            'media_type' => 'tv',
            'seasons' => $details['number_of_seasons'],
            'added_at' => $media['added_at'],
            'genres' => implode(', ', array_map(function ($genre) {
                return $genre['name']; }, $details['genres'] ?? [])) // Formate les genres en chaîne de caractères
        ];
    }
}

// Fonction pour récupérer le réalisateur d'un film
function getDirector($details)
{
    if (isset($details['credits']['crew'])) {
        foreach ($details['credits']['crew'] as $crew) {
            if ($crew['job'] === 'Director') {
                return $crew['name'];
            }
        }
    }
    return 'Inconnu'; // Retourne "Inconnu" si aucun réalisateur n'est trouvé
}

// Fonction pour afficher une date relative (exemple : "il y a 2 jours")
function timeAgo($timestamp)
{
    $time_diff = time() - strtotime($timestamp);

    if ($time_diff < 60) {
        return 'il y a quelques secondes';
    } elseif ($time_diff < 3600) {
        return 'il y a ' . floor($time_diff / 60) . ' minute' . (floor($time_diff / 60) > 1 ? 's' : '');
    } elseif ($time_diff < 86400) {
        return 'il y a ' . floor($time_diff / 3600) . ' heure' . (floor($time_diff / 3600) > 1 ? 's' : '');
    } elseif ($time_diff < 604800) {
        return 'il y a ' . floor($time_diff / 86400) . ' jour' . (floor($time_diff / 86400) > 1 ? 's' : '');
    } elseif ($time_diff < 2592000) {
        return 'il y a ' . floor($time_diff / 604800) . ' semaine' . (floor($time_diff / 604800) > 1 ? 's' : '');
    } elseif ($time_diff < 31536000) {
        return 'il y a ' . floor($time_diff / 2592000) . ' mois';
    } else {
        return 'il y a ' . floor($time_diff / 31536000) . ' an' . (floor($time_diff / 31536000) > 1 ? 's' : '');
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&display=swap"
        rel="stylesheet">
    <title>Ma watchlist - CrunchTime</title>

    <!-- Lien favicons -->
    <link rel="icon" type="image/png" href="../assets/images/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon/favicon.svg" />
    <link rel="shortcut icon" href="../assets/images/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/images/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="CrunchTime" />
    <link rel="manifest" href="../assets/images/favicon/site.webmanifest" />
</head>

<body class="page-watchlist">
    <header>
        <nav class="menu menuOther" role="navigation" aria-label="Menu principal">
            <div class="menuLeft">
                <a href="../index.php" class="logoAccueil" aria-label="Retour à l'accueil">
                    <img src="../assets/images/logo.png" alt="Logo CrunchTime">
                </a>
                <a href="../index.php" class="linkAccueil" aria-label="Lien vers la page d'accueil">Accueil</a>
                <a href="<?php echo isset($_SESSION['user_id']) ? 'swipe.php' : 'login.php'; ?>" aria-label="Lien vers CrunchSwipe">CrunchSwipe</a>
            </div>
            <div class="searchBar" role="search">
                <form action="search.php" method="GET" aria-label="Formulaire de recherche">
                    <img src="../assets/images/icon/search.svg" alt="Icône de recherche">
                    <input type="text" name="query" placeholder="Rechercher..." class="searchInput" required aria-label="Champ de recherche">
                </form>
            </div>
            <div class="menuRight">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="profile" role="menu" aria-label="Menu utilisateur">
                        <img src="../assets/images/profile.png" alt="Image de profil" class="profile-img">
                        <div class="dropdown-menu" role="menu" aria-label="Menu déroulant utilisateur">
                            <img src="../assets/images/profile.png" alt="Image de profil">
                            <p><?= htmlspecialchars($user['username']) ?></p>
                            <a href="profile.php" aria-label="Lien vers mon profil">Mon profil</a>
                            <a href="watchlist.php" aria-label="Lien vers ma watchlist">Ma watchlist</a>
                            <a href="logout.php" id="logout" aria-label="Déconnexion">Déconnexion</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btnLogin" aria-label="Lien vers la page de connexion">Connexion</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    <main>
        <section class="watchlist" role="region" aria-label="Section de la watchlist">
            <div class="watchlistTop">
                <h2>Ma watchlist</h2>
                <?php if (empty($mediaDetails)): ?>
                    <p class="erreurWatchlist" role="alert">Vous n'avez actuellement aucun film dans votre watchlist.</p>
                <?php else: ?>
                </div>
                <div class="buttonNavSearch" role="navigation" aria-label="Navigation dans la liste">
                    <div class="buttonNav">
                        <svg class="prev" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="Précédent">
                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                            <g id="SVGRepo_iconCarrier">
                                <path
                                    d="M13.75 16.25C13.6515 16.2505 13.5538 16.2313 13.4628 16.1935C13.3718 16.1557 13.2893 16.1001 13.22 16.03L9.72001 12.53C9.57956 12.3894 9.50067 12.1988 9.50067 12C9.50067 11.8013 9.57956 11.6107 9.72001 11.47L13.22 8.00003C13.361 7.90864 13.5285 7.86722 13.6958 7.88241C13.8631 7.89759 14.0205 7.96851 14.1427 8.08379C14.2649 8.19907 14.3448 8.35203 14.3697 8.51817C14.3946 8.68431 14.363 8.85399 14.28 9.00003L11.28 12L14.28 15C14.4205 15.1407 14.4994 15.3313 14.4994 15.53C14.4994 15.7288 14.4205 15.9194 14.28 16.06C14.1353 16.1907 13.9448 16.259 13.75 16.25Z"
                                    fill="#000000"></path>
                            </g>
                        </svg>
                        <svg class="next" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="Suivant">
                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                            <g id="SVGRepo_iconCarrier">
                                <path
                                    d="M10.25 16.25C10.1493 16.2466 10.0503 16.2227 9.95921 16.1797C9.86807 16.1367 9.78668 16.0756 9.72001 16C9.57956 15.8594 9.50067 15.6688 9.50067 15.47C9.50067 15.2713 9.57956 15.0806 9.72001 14.94L12.72 11.94L9.72001 8.94002C9.66069 8.79601 9.64767 8.63711 9.68277 8.48536C9.71786 8.33361 9.79933 8.19656 9.91586 8.09322C10.0324 7.98988 10.1782 7.92538 10.3331 7.90868C10.4879 7.89198 10.6441 7.92391 10.78 8.00002L14.28 11.5C14.4205 11.6407 14.4994 11.8313 14.4994 12.03C14.4994 12.2288 14.4205 12.4194 14.28 12.56L10.78 16C10.7133 16.0756 10.6319 16.1367 10.5408 16.1797C10.4497 16.2227 10.3507 16.2466 10.25 16.25Z"
                                    fill="#000000"></path>
                            </g>
                        </svg>
                    </div>
                </div>

                <div class="carousel-container" role="list" aria-label="Liste des médias">
                    <div class="carousel">
                        <?php foreach ($mediaDetails as $media): ?>
                            <div class="movie-card" role="listitem" aria-label="Carte média">
                                <div class="movie-poster">
                                    <a href="details.php?id=<?= $media['id'] ?>&type=<?= $media['media_type'] ?>" aria-label="Voir les détails de <?= htmlspecialchars($media['title']) ?>">
                                        <?php if (!empty($media['poster_path'])): ?>
                                            <img src="https://image.tmdb.org/t/p/w500<?= $media['poster_path'] ?>"
                                                alt="Affiche de <?= htmlspecialchars($media['title']) ?>">
                                        <?php else: ?>
                                            <img src="../assets/images/placeholder_movie.png"
                                                alt="Aucune affiche disponible pour <?= htmlspecialchars($media['title']) ?>" class="placeholder-poster">
                                        <?php endif; ?>
                                    </a>
                                </div>
                                <!-- Titre avec icône à gauche -->
                                <div class="media-title-container">
                                    <!-- Icône du type de média -->
                                    <div class="media-type-icon inline" aria-hidden="true">
                                        <?php if ($media['media_type'] === 'movie'): ?>
                                            <!-- Icône de bobine pour les films -->
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" title="Film">
                                                <path
                                                    d="M0 96C0 60.7 28.7 32 64 32H448c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96zM48 368v32c0 8.8 7.2 16 16 16H96c8.8 0 16-7.2 16-16V368c0-8.8-7.2-16-16-16H64c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16V368c0-8.8-7.2-16-16-16H416zM48 240v32c0 8.8 7.2 16 16 16H96c8.8 0 16-7.2 16-16V240c0-8.8-7.2-16-16-16H64c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16V240c0-8.8-7.2-16-16-16H416zM48 112v32c0 8.8 7.2 16 16 16H96c8.8 0 16-7.2 16-16V112c0-8.8-7.2-16-16-16H64c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16V112c0-8.8-7.2-16-16-16H416z" />
                                            </svg>
                                        <?php else: ?>
                                            <!-- Icône de TV pour les séries -->
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" title="Série">
                                                <path
                                                    d="M64 64V352H576V64H64zM0 64C0 28.7 28.7 0 64 0H576c35.3 0 64 28.7 64 64V352c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V64zM128 448H512c17.7 0 32 14.3 32 32s-14.3 32-32 32H128c-17.7 0-32-14.3-32-32s14.3-32 32-32z" />
                                            </svg>
                                        <?php endif; ?>
                                    </div>

                                    <h4><?= htmlspecialchars($media['title']) ?></h4>
                                </div>

                                <p><?= date('Y', strtotime($media['release_date'])) ?></p>

                                <!-- Affichage de la date d'ajout -->
                                <p class="added-date">Ajouté <?= timeAgo($media['added_at']) ?></p>

                                <!-- Bouton de suppression -->
                                <div class="btnWatchlist btnWatchlistDel">
                                    <button class="button delete-btn" data-id="<?= $media['id'] ?>"
                                        data-type="<?= $media['media_type'] ?>" aria-label="Supprimer <?= htmlspecialchars($media['title']) ?> de la watchlist">
                                        <svg viewBox="0 0 448 512" class="svgIconBtn" aria-hidden="true">
                                            <path
                                                d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z">
                                            </path>
                                        </svg>
                                    </button>
                                </div>


                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
        </section>
    </main>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/watchlist.js"></script>
</body>

</html>