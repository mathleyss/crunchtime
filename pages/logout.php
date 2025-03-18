<?php
session_start(); // Création/démarrage de la session pour la gestion des données utilisateur

// Si une session 'utilisateur' est active, alors...
if (isset($_SESSION['username'])) {

    // session_unset() permet d'effacer toutes les variables d'une session (source : W3Schools)
    // La session est toujours active.
    session_unset();

    // session_destroy() permet de détruire la session (source : W3Schools)
    session_destroy();
}

// Header permet de faire une redirection php ou http
header("Location: logout_success.php");

// Ici, on peut utiliser die() ou exit() pour arrêter le code php (source : Hostinger)
die();
