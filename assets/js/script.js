document.addEventListener("DOMContentLoaded", function() {
    const container = document.querySelector(".trending-movies");
    const prevBtn = document.querySelector(".prev");
    const nextBtn = document.querySelector(".next");

    const scrollAmount = 300; // Distance du défilement

    prevBtn.addEventListener("click", () => {
        container.scrollBy({ left: -scrollAmount, behavior: "smooth" });
    });

    nextBtn.addEventListener("click", () => {
        container.scrollBy({ left: scrollAmount, behavior: "smooth" });
    });

    // Gestion des informations supplémentaires
    const toggleButtons = document.querySelectorAll(".toggle-info");
    toggleButtons.forEach(button => {
        button.addEventListener("click", () => {
            const moreInfo = button.parentElement.previousElementSibling;
            moreInfo.style.display = moreInfo.style.display === "block" ? "none" : "block";
            button.classList.toggle("active");
        });
    });
});