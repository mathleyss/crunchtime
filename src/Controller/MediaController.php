<?php

namespace App\Controller;

use App\Repository\WatchlistRepository;
use App\Service\TmdbService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MediaController extends AbstractController
{
    #[Route('/details/{type}/{id}', name: 'app_media_details', requirements: ['id' => '\d+'])]
    public function details(int $id, string $type, TmdbService $tmdb, WatchlistRepository $watchlistRepository): Response
    {
        $details = $tmdb->getMediaDetails($id, $type);

        if (!$details) {
            throw $this->createNotFoundException('Média introuvable');
        }

        // Vérifier si le média est déjà dans la watchlist de l'utilisateur
        $isInWatchlist = false;
        $user = $this->getUser();
        if ($user) {
            $isInWatchlist = $watchlistRepository->findOneBy([
                'user' => $user,
                'tmdbId' => $id,
                'mediaType' => $type
            ]) !== null;
        }

        return $this->render('media/details.html.twig', [
            'details' => $details,
            'type' => $type,
            'isInWatchlist' => $isInWatchlist,
        ]);
    }

    #[Route('/search', name: 'app_search')]
    public function search(Request $request, TmdbService $tmdb): Response
    {
        $query = $request->query->get('query', '');
        $genre = $request->query->get('genre', '');
        $year = $request->query->get('year', '');

        $results = $tmdb->search($query, $genre, $year);

        // Liste des genres pour le filtre (hardcodé pour simplifier comme dans votre ancien fichier)
        $genres = [
            "28" => "Action", "12" => "Aventure", "16" => "Animation", "35" => "Comédie",
            "80" => "Crime", "99" => "Documentaire", "18" => "Drame", "10751" => "Famille",
            "14" => "Fantaisie", "36" => "Histoire", "27" => "Horreur", "10402" => "Musique",
            "9648" => "Mystère", "10749" => "Romance", "878" => "Science-Fiction",
            "10770" => "Téléfilm", "53" => "Thriller", "10752" => "Guerre", "37" => "Western"
        ];

        return $this->render('media/search.html.twig', [
            'movies' => $results,
            'genres' => $genres,
            'currentQuery' => $query,
            'currentGenre' => $genre,
            'currentYear' => $year
        ]);
    }
}
