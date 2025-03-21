// DOM
const swiper = document.querySelector('#swiper');
const like = document.querySelector('#like');
const dislike = document.querySelector('#dislike');
const swipeInfo = document.querySelector('#swipeInfo');

// Variables
let cardCount = 0;
let currentMovieId = null;  // Variable pour stocker l'ID du film actuel
const apiKey = 'ad3586245e96a667f42a02c1b8708569'; // Remplacez par votre clé API TMDb
let page = 1; // Variable pour changer la page
let movies = []; // Stocke les films avec leurs infos

// Formater la date en "12 Juin 2025"
function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }).format(date);
}

// Récupérer les films et leurs affiches
async function fetchMovies() {
    try {
        const response = await fetch(`https://api.themoviedb.org/3/movie/top_rated?api_key=${apiKey}&language=fr-US&sort_by=popularity.desc&page=${page}`);
        const data = await response.json();
        movies = data.results.map(movie => ({
            id: movie.id,
            title: movie.title,
            posterUrl: movie.poster_path ? `https://image.tmdb.org/t/p/w500${movie.poster_path}` : null,
            releaseDate: formatDate(movie.release_date),
            rating: Math.round(movie.vote_average),
            overview: movie.overview
        })).filter(movie => movie.posterUrl !== null);

        page++;
    } catch (error) {
        console.error('Erreur lors de la récupération des films :', error);
    }
}

async function fetchCredits(movieId) {
    const url = `https://api.themoviedb.org/3/movie/${movieId}/credits?api_key=${apiKey}`;
    try {
        const response = await fetch(url);
        const data = await response.json();
        const director = data.crew.find(person => person.job === "Director")?.name || "Inconnu";
        const actors = data.cast.slice(0, 5).map(actor => ({
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

// Fonction pour mettre à jour la watchlist dynamiquement
function updateWatchlist() {
    fetch('/pages/get_watchlist.php') // Endpoint pour récupérer la watchlist mise à jour
        .then(response => response.text())
        .then(html => {
            const watchlistContainer = document.querySelector('.swipeWatchlist .watchlist-container');
            if (watchlistContainer) {
                watchlistContainer.innerHTML = html; // Mettre à jour le contenu de la watchlist
            }
        })
        .catch(error => {
            console.error('Erreur lors de la mise à jour de la watchlist :', error);
        });
}

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

    fetch("/pages/ajout_watchlist.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
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

    const { director, actors } = await fetchCredits(movie.id);

    swipeInfo.innerHTML = `
        <h2>${movie.title}</h2>
        <p class="date"> ${movie.releaseDate}</p>
        <p><strong>Réalisateur :</strong> ${director}</p>
        <p class="note"><strong>Note :</strong> ${movie.rating}/10</p>
        <p class="resume">${movie.overview}</p>
        <h3>Acteurs principaux :</h3>
        <div class="actors">
            ${actors.map(actor => `
                <div class="actor">
                    <img src="${actor.image}" alt="${actor.name}">
                    <p><strong>${actor.name}</strong> <br> ${actor.character}</p>
                </div>
            `).join('')}
        </div>
    `;
}

// Récupération des films et affichage initial
fetchMovies().then(() => {
    for (let i = 0; i < 5; i++) {
        appendNewCard();
    }
});