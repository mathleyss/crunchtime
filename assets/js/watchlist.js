document.addEventListener("DOMContentLoaded", function () {
    // Ajout ou suppression d'un média dans la watchlist (pour la page details.php)
    const watchlistButtons = document.querySelectorAll(".watchlist-btn");

    watchlistButtons.forEach(button => {
        button.addEventListener("click", function () {
            const mediaId = this.dataset.id; // Récupère l'ID du média
            const action = this.dataset.action; // Récupère l'action (add/remove)
            const mediaType = this.dataset.type || 'movie'; // Récupère le type (movie ou tv)

            console.log(`Action: ${action}, Media ID: ${mediaId}, Type: ${mediaType}`); // Débogage
            
            // Choisir l'URL du script PHP en fonction de l'action (ajout ou suppression)
            const url = action === "add" ? "ajout_watchlist.php" : "suppression_watchlist.php";

            // Effectuer la requête AJAX
        fetch(url, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "media_id=" + encodeURIComponent(mediaId) + 
                  "&media_type=" + encodeURIComponent(mediaType) + 
                  "&previousPage=" + encodeURIComponent(window.location.href)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'interface utilisateur (changer le texte et l'action du bouton)
                    if (action === "add") {
                        this.textContent = "Supprimer de la watchlist";
                        this.dataset.action = "remove";
                    } else {
                        this.textContent = "Ajouter à la watchlist";
                        this.dataset.action = "add";
                    }
                } else {
                    alert(data.message); // Afficher un message en cas d'erreur
                }
            })
            .catch(error => console.error("Erreur:", error));
        });
    });

    // Suppression d'un média de la watchlist (depuis watchlist.php)
    const deleteButtons = document.querySelectorAll(".delete-btn");

    deleteButtons.forEach(button => {
        button.addEventListener("click", function () {
            const mediaId = this.dataset.id;

            fetch("suppression_watchlist.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "media_id=" + encodeURIComponent(mediaId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Supprimer la carte du film de l'affichage
                    const movieCard = this.closest(".movie-card");
                    if (movieCard) {
                        movieCard.remove();
                    }
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error("Erreur:", error));
        });
    });

    // Ajout d'un média dans la watchlist (pour la page index.php)
    document.querySelectorAll(".toggle-watchlist").forEach(button => {
        button.addEventListener("click", function () {
            let movieId = this.dataset.id;
            let action = this.dataset.action;
            let mediaType = this.dataset.type || 'movie'; // Récupérer le type (movie ou tv)
            let buttonElement = this;
            let buttonParent = buttonElement.parentElement; // Récupère le div parent du bouton
    
            // Vérifier si l'action est d'ajouter (nous ne permettons que l'ajout)
            if (action === "add") {
                fetch("pages/toggle_watchlist.php", {
                    method: "POST",
                    body: JSON.stringify({ 
                        media_id: movieId, 
                        action: "add",
                        media_type: mediaType 
                    }),
                    headers: { "Content-Type": "application/json" }
                })
                .then(response => response.json()) // Résultat JSON en réponse
                .then(data => {
                    if (data.success) {
                        // Créer un nouveau bouton (non cliquable) avec la coche verte
                        const successButton = document.createElement('button');
                        successButton.className = 'success-check';
                        successButton.disabled = true; // Non cliquable
                        successButton.innerHTML = `
                            <svg class="svgIconBtn checkIcon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"></path>
                            </svg>
                        `;
                        
                        // Modifier les classes du parent pour enlever les comportements de hover
                        buttonParent.classList.remove('btnWatchlist', 'btnWatchlistAdd');
                        buttonParent.classList.add('watchlist-added-indicator');
                        
                        // Supprimer l'ancien bouton et ajouter le nouveau
                        buttonParent.innerHTML = '';
                        buttonParent.appendChild(successButton);
                        
                        // Mettre à jour le compteur de watchlist
                        const watchlistCounter = document.querySelector(".btnWatch");
                        if (watchlistCounter) {
                            watchlistCounter.textContent = data.watchlistCount;
                        }
                    } else {
                        alert("Erreur : " + data.message); // Affichage de l'erreur si l'ajout échoue
                    }
                })
                .catch(error => console.error("Erreur :", error)); // Gestion des erreurs de requête
            }
        });
    });
});