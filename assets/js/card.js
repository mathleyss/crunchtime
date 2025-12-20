document.addEventListener("turbo:load", function () {
    // Gestion des popups sur les cartes
    const cards = document.querySelectorAll('.movie-card');

    cards.forEach(card => {
        const moreInfoBtn = card.querySelector('.btn-more-info');
        const popup = card.querySelector('.card-popup');

        if (moreInfoBtn && popup) {
            // Ouvrir le popup
            moreInfoBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                // Fermer les autres popups ouverts
                document.querySelectorAll('.card-popup.visible').forEach(p => {
                    if (p !== popup) {
                        p.classList.remove('visible');
                        setTimeout(() => p.classList.add('hidden'), 300);
                    }
                });

                popup.classList.remove('hidden');
                // Petit délai pour permettre la transition CSS
                requestAnimationFrame(() => {
                    popup.classList.add('visible');
                });
            });

            // Fermer le popup au clic n'importe où ailleurs sur la carte ou le popup
            popup.addEventListener('click', (e) => {
                // Si on clique sur un lien ou un bouton à l'intérieur, on laisse faire
                if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON') {
                    return;
                }

                popup.classList.remove('visible');
                setTimeout(() => {
                    popup.classList.add('hidden');
                }, 300);
            });

            // Fermer le popup si la souris quitte la carte (optionnel, peut être gênant sur mobile)
            card.addEventListener('mouseleave', () => {
                if (popup.classList.contains('visible')) {
                    popup.classList.remove('visible');
                    setTimeout(() => {
                        popup.classList.add('hidden');
                    }, 300);
                }
            });
        }
    });

    // Fermer les popups si on clique en dehors de tout
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.movie-card')) {
            document.querySelectorAll('.card-popup.visible').forEach(p => {
                p.classList.remove('visible');
                setTimeout(() => p.classList.add('hidden'), 300);
            });
        }
    });
});
