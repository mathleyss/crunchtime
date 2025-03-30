class Card {
    constructor({
        imageUrl,
        movieId, // Nouvel attribut pour identifier le film
        onDismiss, // Callback appelé lors de la suppression de la carte
        onLike, // Callback appelé lors d'un "like"
        onDislike // Callback appelé lors d'un "dislike"
    }) {
        this.imageUrl = imageUrl;
        this.movieId = movieId; // Stocker l'ID du film
        this.onDismiss = onDismiss;
        this.onLike = onLike;
        this.onDislike = onDislike;
        this.#init(); // Initialisation de la carte
    }

    // Propriétés privées pour gérer les interactions
    #startPoint; // Point de départ du mouvement (souris ou tactile)
    #offsetX; // Décalage horizontal
    #offsetY; // Décalage vertical

    // Vérifie si l'appareil est tactile
    #isTouchDevice = () => {
        return (('ontouchstart' in window) ||
            (navigator.maxTouchPoints > 0) ||
            (navigator.msMaxTouchPoints > 0));
    }

    // Initialisation de la carte
    #init = () => {
        const card = document.createElement('div');
        card.classList.add('card');
        card.dataset.id = this.movieId; // Associer l'ID du film à l'élément DOM
        const img = document.createElement('img');
        img.src = this.imageUrl; // Ajouter l'image à la carte
        card.append(img);
        this.element = card;

        // Détecter le type d'appareil et écouter les événements appropriés
        if (this.#isTouchDevice()) {
            this.#listenToTouchEvents();
        } else {
            this.#listenToMouseEvents();
        }
    }

    // Gestion des événements tactiles
    #listenToTouchEvents = () => {
        this.element.addEventListener('touchstart', (e) => {
            const touch = e.changedTouches[0];
            if (!touch) return;
            const {
                clientX,
                clientY
            } = touch;
            this.#startPoint = {
                x: clientX,
                y: clientY
            }
            document.addEventListener('touchmove', this.#handleTouchMove);
            this.element.style.transition = 'transform 0s'; // Désactiver la transition pendant le mouvement
        });

        document.addEventListener('touchend', this.#handleTouchEnd);
        document.addEventListener('cancel', this.#handleTouchEnd);
    }

    // Gestion des événements souris
    #listenToMouseEvents = () => {
        this.element.addEventListener('mousedown', (e) => {
            const {
                clientX,
                clientY
            } = e;
            this.#startPoint = {
                x: clientX,
                y: clientY
            }
            document.addEventListener('mousemove', this.#handleMouseMove);
            this.element.style.transition = 'transform 0s'; // Désactiver la transition pendant le mouvement
        });

        document.addEventListener('mouseup', this.#handleMoveUp);

        // Empêcher la carte d'être glissée par défaut
        this.element.addEventListener('dragstart', (e) => {
            e.preventDefault();
        });
    }

    // Gère le mouvement de la carte (souris ou tactile)
    #handleMove = (x, y) => {
        this.#offsetX = x - this.#startPoint.x; // Calculer le décalage horizontal
        this.#offsetY = y - this.#startPoint.y; // Calculer le décalage vertical
        const rotate = this.#offsetX * 0.1; // Calculer la rotation en fonction du décalage
        this.element.style.transform = `translate(${this.#offsetX}px, ${this.#offsetY}px) rotate(${rotate}deg)`;

        // Si le décalage horizontal dépasse un seuil, supprimer la carte
        if (Math.abs(this.#offsetX) > this.element.clientWidth * 0.7) {
            this.#dismiss(this.#offsetX > 0 ? 1 : -1); // 1 pour droite, -1 pour gauche
        }
    }

    // Gestionnaire de mouvement pour la souris
    #handleMouseMove = (e) => {
        e.preventDefault();
        if (!this.#startPoint) return; // Si aucun point de départ, ne rien faire
        const {
            clientX,
            clientY
        } = e;
        this.#handleMove(clientX, clientY); // Appeler la logique de mouvement
    }

    // Gestionnaire de relâchement de la souris
    #handleMoveUp = () => {
        this.#startPoint = null; // Réinitialiser le point de départ
        document.removeEventListener('mousemove', this.#handleMouseMove); // Arrêter d'écouter les mouvements
        this.element.style.transform = ''; // Réinitialiser la transformation
    }

    // Gestionnaire de mouvement pour le tactile
    #handleTouchMove = (e) => {
        if (!this.#startPoint) return; // Si aucun point de départ, ne rien faire
        const touch = e.changedTouches[0];
        if (!touch) return;
        const {
            clientX,
            clientY
        } = touch;
        this.#handleMove(clientX, clientY); // Appeler la logique de mouvement
    }

    // Gestionnaire de fin de mouvement tactile
    #handleTouchEnd = () => {
        this.#startPoint = null; // Réinitialiser le point de départ
        document.removeEventListener('touchmove', this.#handleTouchMove); // Arrêter d'écouter les mouvements
        this.element.style.transform = ''; // Réinitialiser la transformation
    }

    // Supprimer la carte avec une animation
    #dismiss = (direction) => {
        this.#startPoint = null; // Réinitialiser le point de départ
        document.removeEventListener('mouseup', this.#handleMoveUp);
        document.removeEventListener('mousemove', this.#handleMouseMove);
        document.removeEventListener('touchend', this.#handleTouchEnd);
        document.removeEventListener('touchmove', this.#handleTouchMove);

        // Ajouter une animation de suppression
        this.element.style.transition = 'transform 1s';
        this.element.style.transform = `translate(${direction * window.innerWidth}px, ${this.#offsetY}px) rotate(${90 * direction}deg)`;
        this.element.classList.add('dismissing');

        // Supprimer l'élément après l'animation
        setTimeout(() => {
            this.element.remove();
            updateDisplayedMovieInfo(); // Mettre à jour les informations affichées
        }, 1000);

        // Appeler les callbacks appropriés
        if (typeof this.onDismiss === 'function') {
            this.onDismiss();
        }
        if (typeof this.onLike === 'function' && direction === 1) {
            this.onLike();
        }
        if (typeof this.onDislike === 'function' && direction === -1) {
            this.onDislike();
        }
    }
}
