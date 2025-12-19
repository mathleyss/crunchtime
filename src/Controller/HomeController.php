<?php

namespace App\Controller;

use App\Repository\WatchlistRepository;
use App\Service\TmdbService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(TmdbService $tmdb, WatchlistRepository $watchlistRepo): Response
    {
        // Récupérer la watchlist de l'utilisateur pour savoir quels films cocher
        $userWatchlist = [];
        if ($this->getUser()) {
            $items = $watchlistRepo->findBy(['user' => $this->getUser()]);
            foreach ($items as $item) {
                $userWatchlist[$item->getMediaType() . '_' . $item->getTmdbId()] = true;
            }
        }

        return $this->render('home/index.html.twig', [
            'trendingMovies' => $tmdb->getTrendingMovies(),
            'trendingSeries' => $tmdb->getTrendingSeries(),
            'actionMovies' => $tmdb->getActionMovies(),
            'topRatedSeries' => $tmdb->getTopRatedSeries(),
            'userWatchlist' => $userWatchlist,
        ]);
    }
}
