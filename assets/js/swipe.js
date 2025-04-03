// CRÉDIT DE LA FONCTION DU SWIPE : 
// https://github.com/CodeSteppe/card-swiper?tab=readme-ov-file

// DOM : Récupération des éléments HTML nécessaires
const swiper = document.querySelector('#swiper');
const like = document.querySelector('#like');
const dislike = document.querySelector('#dislike');
const swipeInfo = document.querySelector('#swipeInfo');
const baseURL = '/crunchtime/pages/';

// Variables globales
let cardCount = 0; // Compteur pour suivre le nombre de cartes affichées
let currentMovieId = null; // ID du film actuellement affiché
const apiKey = 'ad3586245e96a667f42a02c1b8708569'; // Clé API pour TMDb
let page = 1; // Page actuelle pour la pagination des films
let movies = []; // Tableau pour stocker les films récupérés

// Fonction pour formater une date et récupérer uniquement l'année
function formatYear(dateString) {
    const date = new Date(dateString);
    return date.getFullYear();
}

// Liste des genres de films avec leurs correspondances TMDb
const genreMap = {
    28: "Action",
    12: "Aventure",
    16: "Animation",
    35: "Comédie",
    80: "Crime",
    99: "Documentaire",
    18: "Drame",
    10751: "Familial",
    14: "Fantastique",
    36: "Histoire",
    27: "Horreur",
    10402: "Musique",
    9648: "Mystère",
    10749: "Romance",
    878: "Science-Fiction",
    10770: "Téléfilm",
    53: "Thriller",
    10752: "Guerre",
    37: "Western"
};

// Fonction pour récupérer les films populaires depuis l'API TMDb
async function fetchMovies() {
    try {
        // Sélection d'une page aléatoire pour varier les résultats
        const randomPage = Math.floor(Math.random() * 300) + 1;

        const response = await fetch(`https://api.themoviedb.org/3/movie/popular?api_key=${apiKey}&language=fr-US&page=${randomPage}`);
        const data = await response.json();

        // Transformation des données pour inclure uniquement les informations nécessaires
        movies = data.results.map(movie => ({
            id: movie.id,
            title: movie.title,
            posterUrl: movie.poster_path ? `https://image.tmdb.org/t/p/w500${movie.poster_path}` : null,
            releaseDate: formatYear(movie.release_date),
            rating: Math.round(movie.vote_average),
            genres: movie.genre_ids.map(id => genreMap[id] || "Inconnu"),
            director: movie.director || "Inconnu",
            overview: movie.overview
        })).filter(movie => movie.posterUrl !== null); // Filtrer les films sans affiche

        page++; // Incrémenter la page pour la prochaine requête
    } catch (error) {
        console.error('Erreur lors de la récupération des films :', error);
    }
}

// Fonction pour récupérer la durée d'un film
async function fetchMovieDuration(movieId) {
    try {
        const response = await fetch(`https://api.themoviedb.org/3/movie/${movieId}?api_key=${apiKey}&language=fr-US`);
        const data = await response.json();
        return data.runtime || 0; // Retourne la durée en minutes ou 0 si indisponible
    } catch (error) {
        console.error("Erreur lors de la récupération de la durée :", error);
        return 0;
    }
}

// Fonction pour générer des étoiles en fonction de la note du film
function generateStars(rating) {
    const normalizedRating = rating / 2; // Conversion de la note sur 10 en note sur 5
    const fullStars = Math.floor(normalizedRating); // Étoiles pleines
    const halfStar = (normalizedRating - fullStars) >= 0.5 ? 1 : 0; // Demi-étoile si nécessaire
    const emptyStars = 5 - fullStars - halfStar; // Étoiles vides

    let starsHTML = '';
    starsHTML += '★'.repeat(fullStars); // Ajouter les étoiles pleines
    if (halfStar) starsHTML += '★'; // Ajouter une demi-étoile
    starsHTML += '☆'.repeat(emptyStars); // Ajouter les étoiles vides

    return starsHTML;
}

// Fonction pour récupérer les crédits d'un film (réalisateur et acteurs principaux)
async function fetchCredits(movieId) {
    const url = `https://api.themoviedb.org/3/movie/${movieId}/credits?api_key=${apiKey}`;
    try {
        const response = await fetch(url);
        const data = await response.json();
        const director = data.crew.find(person => person.job === "Director")?.name || "Inconnu";
        const actors = data.cast.slice(0, 4).map(actor => ({
            name: actor.name,
            character: actor.character,
            image: actor.profile_path ? `https://image.tmdb.org/t/p/w200${actor.profile_path}` : 'https://via.placeholder.com/200'
        }));
        return { director, actors };
    } catch (error) {
        console.error('Erreur lors de la récupération des crédits :', error);
        return { director: "Inconnu", actors: [] };
    }
}

// Fonction pour mettre à jour dynamiquement la watchlist
function updateWatchlist() {
    fetch(`get_watchlist.php`)
        .then(response => response.text())
        .then(html => {
            const watchlistContainer = document.querySelector('.swipeWatchlist .watchlist-container');
            if (!watchlistContainer) return;

            watchlistContainer.innerHTML = html; // Mettre à jour le contenu de la watchlist
            sessionStorage.setItem("swipeWatchlist", watchlistContainer.innerHTML); // Sauvegarder dans sessionStorage
        })
        .catch(error => console.error('Erreur lors de la mise à jour de la watchlist :', error));
}

// Restaurer la watchlist après un rechargement de la page
document.addEventListener("DOMContentLoaded", () => {
    const savedWatchlist = sessionStorage.getItem("swipeWatchlist");
    if (savedWatchlist) {
        const watchlistContainer = document.querySelector('.swipeWatchlist .watchlist-container');
        if (watchlistContainer) {
            watchlistContainer.innerHTML = savedWatchlist;
        }
    }
    updateWatchlist(); // Récupérer les films depuis la base de données
});

// Fonction pour ajouter un film à la watchlist
function addToWatchlist() {
    if (!currentMovieId) {
        console.error("Aucun ID de film actuel trouvé.");
        return;
    }

    const currentMovie = movies.find(m => m.id == currentMovieId);
    if (!currentMovie) {
        console.error("Film non trouvé dans la liste.");
        return;
    }

    const mediaType = "movie";

    fetch(`ajout_watchlist.php`, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `media_id=${encodeURIComponent(currentMovieId)}&media_type=${encodeURIComponent(mediaType)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log("Film ajouté à la watchlist :", currentMovie.title);
            updateWatchlist(); // Mettre à jour la watchlist après ajout
        } else {
            console.error("Erreur lors de l'ajout à la watchlist :", data.message);
        }
    })
    .catch(error => console.error("Erreur lors de la requête AJAX :", error));
}

// Fonction pour ajouter une nouvelle carte avec un film
function appendNewCard() {
    if (movies.length === 0) return;

    const movie = movies[cardCount % movies.length]; // Sélectionner un film
    const card = new Card({
        imageUrl: movie.posterUrl,
        movieId: movie.id,
        onDismiss: () => {
            appendNewCard(); // Ajouter une nouvelle carte après suppression
            updateDisplayedMovieInfo(); // Mettre à jour les infos affichées
        },
        onLike: () => {
            currentMovieId = movie.id; // Enregistrer l'ID du film
            addToWatchlist(); // Ajouter le film à la watchlist
            like.style.animationPlayState = 'running';
            like.classList.toggle('trigger');
        },
        onDislike: () => {
            currentMovieId = movie.id; // Enregistrer l'ID du film
            dislike.style.animationPlayState = 'running';
            dislike.classList.toggle('trigger');
        }
    });

    // Ajouter un rôle et des attributs ARIA pour l'accessibilité
    card.element.setAttribute('role', 'article');
    card.element.setAttribute('aria-label', `Carte du film : ${movie.title}`);

    swiper.append(card.element); // Ajouter la carte au conteneur
    cardCount++; // Incrémenter le compteur

    updateDisplayedMovieInfo(); // Mettre à jour les infos du film affiché

    // Réorganiser les cartes pour l'effet visuel
    const cards = swiper.querySelectorAll('.card:not(.dismissing)');
    cards.forEach((card, index) => {
        card.style.setProperty('--i', index);
    });
}

// Fonction pour mettre à jour les informations du film affiché
async function updateDisplayedMovieInfo() {
    const topCard = swiper.querySelector('.card:not(.dismissing)');
    if (!topCard) return;

    const movieId = topCard.dataset.id; // Récupérer l'ID du film
    const movie = movies.find(m => m.id == movieId);
    if (!movie) return;

    const { director, actors } = await fetchCredits(movie.id); // Récupérer les crédits
    const duration = await fetchMovieDuration(movie.id); // Récupérer la durée
    const hours = Math.floor(duration / 60);
    const minutes = duration % 60;
    const durationText = duration ? `${hours}h ${minutes}min` : "Inconnue";

    // Mettre à jour le contenu HTML avec les infos du film
    swipeInfo.innerHTML = `
        <h2>${movie.title}</h2>
        <p class="date">${new Date(movie.releaseDate).getFullYear()}</p>
        <p class="duration">${durationText}</p>
        <p class="real">Réalisé par <i>${director}</i></p>
        <p class="genres">${movie.genres.join(", ")}</p>
        <p class="star-rating">${generateStars(movie.rating)}</p>
        <p class="resume">${movie.overview}</p>
        <div class="actors" role="list" aria-label="Liste des acteurs principaux">
            ${actors.map(actor => `
                <div class="actor" role="listitem" aria-label="Acteur : ${actor.name}, rôle : ${actor.character}">
                    <img src="${actor.image || '../assets/images/placeholder_actor.png'}" alt="Photo de ${actor.name}" onError="this.onerror=null;this.src='../assets/images/placeholder_actor.png';">
                    <p><strong>${actor.name}</strong> <br> ${actor.character}</p>
                </div>
            `).join('')}
        </div>
        <a href="details.php?id=${movie.id}" class="swipeBtnDetails" role="button" aria-label="Voir les détails du film ${movie.title}">Voir les détails</a>
    `;
}

// Récupérer les films et afficher les premières cartes
fetchMovies().then(() => {
    for (let i = 0; i < 4; i++) {
        appendNewCard(); // Ajouter 4 cartes initiales
    }
});
