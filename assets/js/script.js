document.addEventListener("DOMContentLoaded", function() {
    // Carrousel des films récents
    const recentContainer = document.querySelector(".filmsRecents .carousel");
    const recentPrevBtn = document.querySelector(".filmsRecents .prev");
    const recentNextBtn = document.querySelector(".filmsRecents .next");

    const scrollAmount = 300; // Distance du défilement

    recentPrevBtn.addEventListener("click", () => {
        recentContainer.scrollBy({ left: -scrollAmount, behavior: "smooth" });
    });

    recentNextBtn.addEventListener("click", () => {
        recentContainer.scrollBy({ left: scrollAmount, behavior: "smooth" });
    });

    // Carrousel des séries tendances
    const seriesContainer = document.querySelector(".seriesTendances .carousel");
    const seriesPrevBtn = document.querySelector(".seriesTendances .prev");
    const seriesNextBtn = document.querySelector(".seriesTendances .next");

    seriesPrevBtn.addEventListener("click", () => {
        seriesContainer.scrollBy({ left: -scrollAmount, behavior: "smooth" });
    });

    seriesNextBtn.addEventListener("click", () => {
        seriesContainer.scrollBy({ left: scrollAmount, behavior: "smooth" });
    });

    // Carrousel des films d'action
    const actionContainer = document.querySelector(".filmsAction .carousel");
    const actionPrevBtn = document.querySelector(".filmsAction .prev");
    const actionNextBtn = document.querySelector(".filmsAction .next");

    actionPrevBtn.addEventListener("click", () => {
        actionContainer.scrollBy({ left: -scrollAmount, behavior: "smooth" });
    });

    actionNextBtn.addEventListener("click", () => {
        actionContainer.scrollBy({ left: scrollAmount, behavior: "smooth" });
    });

    // Carrousel des séries les mieux notées
    const topRatedContainer = document.querySelector(".seriesTopRated .carousel");
    const topRatedPrevBtn = document.querySelector(".seriesTopRated .prev");
    const topRatedNextBtn = document.querySelector(".seriesTopRated .next");

    topRatedPrevBtn.addEventListener("click", () => {
        topRatedContainer.scrollBy({ left: -scrollAmount, behavior: "smooth" });
    });

    topRatedNextBtn.addEventListener("click", () => {
        topRatedContainer.scrollBy({ left: scrollAmount, behavior: "smooth" });
    });

    // Gestion des informations supplémentaires pour tous les carrousels
    document.querySelectorAll(".toggle-info").forEach(button => {
        button.addEventListener("click", () => {
            const moreInfo = button.previousElementSibling; // Sélectionne .more-info
            moreInfo.classList.toggle("active");
            button.classList.toggle("active");
        });
    });
});