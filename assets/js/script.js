// Fonction pour le carrousel
document.addEventListener("DOMContentLoaded", function () {
    const scrollAmount = 300; // Distance du défilement

    function initCarousel(containerSelector) {
        const container = document.querySelector(`${containerSelector} .carousel`);
        const prevBtn = document.querySelector(`${containerSelector} .prev`);
        const nextBtn = document.querySelector(`${containerSelector} .next`);

        if (container && prevBtn && nextBtn) {
            prevBtn.addEventListener("click", () => {
                container.scrollBy({
                    left: -scrollAmount,
                    behavior: "smooth"
                });
            });

            nextBtn.addEventListener("click", () => {
                container.scrollBy({
                    left: scrollAmount,
                    behavior: "smooth"
                });
            });
        }
    }

    // Initialiser les carrousels présents sur la page
    const carousels = [
        ".filmsRecents",
        ".seriesTendances",
        ".filmsAction",
        ".seriesTopRated",
        ".watchlist",
        ".searchPage"
    ];

    carousels.forEach(initCarousel);


    // Pop-up de message pour le site
    const message = document.querySelector('.messageWatchlist');
    if (message) {
        setTimeout(() => {
            message.classList.add('masque'); // ajout de la classe masque à l'élément pour faire disparaître progressivement l'écran de démarrage
            setTimeout(() => { // Déclenchement de la fonction après 0.5s (500ms)
                message.classList.add('supprime'); // ajout de la classe supprime à l'élément pour effacer l'élément
            }, 500);
        }, 1000);
    }
});
