// DOM
const swiper = document.querySelector('#swiper');
const like = document.querySelector('#like');
const dislike = document.querySelector('#dislike');
const swipeInfo = document.querySelector('#swipeInfo');
const baseURL = '/crunchtime/pages/';

// Variables
let cardCount = 0;
let currentMovieId = null; // Variable pour stocker l'ID du film actuel
const apiKey = 'ad3586245e96a667f42a02c1b8708569'; // Remplacez par votre clé API TMDb
let page = 1; // Variable pour changer la page
let movies = []; // Stocke les films avec leurs infos

// Formater la date 
function formatYear(dateString) {
    const date = new Date(dateString);
    return date.getFullYear(); // Récupère seulement l'année
}
// Liste des genres TMDb
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

// Récupérer les films et leurs affiches
async function fetchMovies() {
    try {
        // Sélectionner une page aléatoire entre 1 et 300
        const randomPage = Math.floor(Math.random() * 300) + 1;

        const response = await fetch(`https://api.themoviedb.org/3/movie/popular?api_key=${apiKey}&language=fr-US&page=${randomPage}`);
        const data = await response.json();

        movies = data.results.map(movie => ({
            id: movie.id,
            title: movie.title,
            posterUrl: movie.poster_path ? `https://image.tmdb.org/t/p/w500${movie.poster_path}` : null,
            releaseDate: formatYear(movie.release_date), // Utilisation de formatYear pour récupérer l'année
            rating: Math.round(movie.vote_average),
            genres: movie.genre_ids.map(id => genreMap[id] || "Inconnu"),
            director: movie.director || "Inconnu", // Ajout du réalisateur
            overview: movie.overview
        })).filter(movie => movie.posterUrl !== null);

        page++; // On change de page pour les prochains chargements
    } catch (error) {
        console.error('Erreur lors de la récupération des films :', error);
    }
}

async function fetchMovieDuration(movieId) {
    try {
        const response = await fetch(`https://api.themoviedb.org/3/movie/${movieId}?api_key=${apiKey}&language=fr-US`);
        const data = await response.json();
        return data.runtime || 0; // Retourne la durée en minutes (ou 0 si indisponible)
    } catch (error) {
        console.error("Erreur lors de la récupération de la durée :", error);
        return 0;
    }
}

// Fonction pour générer les étoiles
function generateStars(rating) {
    // Convertir la note sur 10 en note sur 5
    const normalizedRating = rating / 2;

    const fullStars = Math.floor(normalizedRating);
    const halfStar = (normalizedRating - fullStars) >= 0.5 ? 1 : 0;
    const emptyStars = 5 - fullStars - halfStar;

    let starsHTML = '';

    // Générer les étoiles pleines
    starsHTML += '★'.repeat(fullStars);

    // Ajouter une demi-étoile si nécessaire
    if (halfStar) {
        starsHTML += '★';
    }

    // Ajouter les étoiles vides
    starsHTML += '☆'.repeat(emptyStars);

    return starsHTML;
}

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
        return {
            director,
            actors
        };
    } catch (error) {
        console.error('Erreur lors de la récupération des crédits :', error);
        return {
            director: "Inconnu",
            actors: []
        };
    }
}

// Fonction pour mettre à jour la watchlist dynamiquement
function updateWatchlist() {
    fetch(`get_watchlist.php`)
        .then(response => response.text())
        .then(html => {
            const watchlistContainer = document.querySelector('.swipeWatchlist .watchlist-container');
            if (!watchlistContainer) return;

            // Met à jour le contenu de la watchlist
            watchlistContainer.innerHTML = html;

            // Sauvegarder dans sessionStorage pour garder l'affichage même après un rechargement
            sessionStorage.setItem("swipeWatchlist", watchlistContainer.innerHTML);
        })
        .catch(error => console.error('Erreur lors de la mise à jour de la watchlist :', error));
}

// Restaurer la watchlist après un rechargement de page
document.addEventListener("DOMContentLoaded", () => {
    const savedWatchlist = sessionStorage.getItem("swipeWatchlist");

    // Afficher les films de la watchlist si présents dans sessionStorage
    if (savedWatchlist) {
        const watchlistContainer = document.querySelector('.swipeWatchlist .watchlist-container');
        if (watchlistContainer) {
            watchlistContainer.innerHTML = savedWatchlist;
        }
    }

    // Ensuite, appeler `updateWatchlist()` pour récupérer les films en base de données
    updateWatchlist();
});

// Modifier la fonction addToWatchlist pour inclure la mise à jour de la watchlist
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
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "media_id=" + encodeURIComponent(currentMovieId) +
                "&media_type=" + encodeURIComponent(mediaType)
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
        .catch(error => {
            console.error("Erreur lors de la requête AJAX :", error);
        });
}

// Ajouter une nouvelle carte avec une affiche de film
function appendNewCard() {
    if (movies.length === 0) return;

    const movie = movies[cardCount % movies.length];
    const card = new Card({
        imageUrl: movie.posterUrl,
        movieId: movie.id,
        onDismiss: () => {
            appendNewCard();
            updateDisplayedMovieInfo(); // Mettre à jour après suppression
        },
        onLike: () => {
            // Enregistrer l'ID avant de lancer l'animation
            currentMovieId = movie.id;

            // Ajouter directement le film à la watchlist
            addToWatchlist();

            like.style.animationPlayState = 'running';
            like.classList.toggle('trigger');
        },
        onDislike: () => {
            // Enregistrer l'ID avant de lancer l'animation
            currentMovieId = movie.id;

            dislike.style.animationPlayState = 'running';
            dislike.classList.toggle('trigger');
        }
    });

    swiper.append(card.element);
    cardCount++;

    updateDisplayedMovieInfo(); // Mise à jour après chaque ajout

    const cards = swiper.querySelectorAll('.card:not(.dismissing)');
    cards.forEach((card, index) => {
        card.style.setProperty('--i', index);
    });
}

// Mettre à jour les infos du film affiché
async function updateDisplayedMovieInfo() {
    const topCard = swiper.querySelector('.card:not(.dismissing)');
    if (!topCard) return;

    const movieId = topCard.dataset.id; // Récupérer l'ID du film affiché
    const movie = movies.find(m => m.id == movieId);
    if (!movie) return;

    const {
        director,
        actors
    } = await fetchCredits(movie.id);
    const duration = await fetchMovieDuration(movie.id);
    const hours = Math.floor(duration / 60);
    const minutes = duration % 60;
    const durationText = duration ? `${hours}h ${minutes}min` : "Inconnue";

    swipeInfo.innerHTML = `
        <h2>${movie.title}</h2>
        <p class="date">${new Date(movie.releaseDate).getFullYear()}</p>
        <p class="duration">${durationText}</p>
        <p class="real">Réalisé par <i>${director}</i></p>
        <p class="genres">${movie.genres.join(", ")}</p>
        <p class="star-rating">${generateStars(movie.rating)}</p> <!-- Remplacer la note par des étoiles -->
        <p class="resume">${movie.overview}</p>
        <div class="actors">
            ${actors.map(actor => `
                <div class="actor">
                    <img src="${actor.image || '../assets/images/placeholder_actor.png'}" alt="${actor.name}" onError="this.onerror=null;this.src='../assets/images/placeholder_actor.png';">
                    <p><strong>${actor.name}</strong> <br> ${actor.character}</p>
                </div>
            `).join('')}
        </div>
        <a href="details.php?id=${movie.id}" class="swipeBtnDetails">Voir les détails</a>
    `;
}

// Récupération des films et affichage initial
fetchMovies().then(() => {
    for (let i = 0; i < 4; i++) {
        appendNewCard();
    }
});
