// Fonction pour le carrousel
document.addEventListener("turbo:load", function () {
    const scrollAmount = 300; // Distance du défilement

    function initCarousel(containerSelector) {
        // On cherche le conteneur parent qui a la classe passée en paramètre
        // Mais attention, dans le nouveau template, la classe est sur <section class="carousel-section">
        // et on a aussi <div class="catFilmsRecents"> pour les boutons

        // On va plutôt chercher par section
        const sections = document.querySelectorAll('section.carousel-section');

        sections.forEach(section => {
            const container = section.querySelector('.carousel');
            const prevBtn = section.querySelector('.prev');
            const nextBtn = section.querySelector('.next');

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
        });

        // Gestion des anciens sélecteurs si encore utilisés ailleurs (ex: watchlist)
        const oldCarousels = [
            ".watchlist",
            ".searchPage"
        ];

        oldCarousels.forEach(selector => {
            const el = document.querySelector(selector);
            if (el) {
                const container = el.querySelector('.carousel');
                const prevBtn = el.querySelector('.prev');
                const nextBtn = el.querySelector('.next');

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
        });
    }

    initCarousel();


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
