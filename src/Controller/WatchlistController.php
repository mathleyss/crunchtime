<?php

namespace App\Controller;

use App\Entity\Watchlist;
use App\Repository\WatchlistRepository;
use App\Service\TmdbService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')] // Sécurité : Il faut être connecté
class WatchlistController extends AbstractController
{
    #[Route('/watchlist', name: 'app_watchlist', methods: ['GET'])]
    public function index(WatchlistRepository $repo, TmdbService $tmdb): Response
    {
        $user = $this->getUser();
        // On récupère les films du plus récent au plus ancien
        $watchlistItems = $repo->findBy(['user' => $user], ['addedAt' => 'DESC']);

        $mediaList = [];
        foreach ($watchlistItems as $item) {
            // On récupère les détails frais depuis l'API (avec cache)
            $details = $tmdb->getMediaDetails($item->getTmdbId(), $item->getMediaType());
            
            if ($details) {
                // On ajoute les infos locales (date d'ajout, type)
                $details['added_at'] = $item->getAddedAt();
                $details['media_type'] = $item->getMediaType();
                $details['db_id'] = $item->getId();
                
                // Le titre et l'image sont déjà dans $details via l'API
                $mediaList[] = $details;
            }
        }

        return $this->render('watchlist/index.html.twig', [
            'mediaList' => $mediaList,
        ]);
    }

    #[Route('/watchlist/toggle/{type}/{id}', name: 'watchlist_toggle')]
    public function toggle(string $type, int $id, Request $request, TmdbService $tmdb, EntityManagerInterface $em, WatchlistRepository $repo): Response
    {
        $user = $this->getUser();
        $isInWatchlist = false;
        
        // 1. On vérifie si c'est déjà dans la liste
        $existing = $repo->findOneBy(['user' => $user, 'tmdbId' => $id, 'mediaType' => $type]);

        if ($existing) {
            // SI OUI : On supprime
            $em->remove($existing);
        } else {
            // SI NON : On ajoute
            $details = $tmdb->getMediaDetails($id, $type);
            if ($details) {
                $watchlist = new Watchlist();
                $watchlist->setUser($user);
                $watchlist->setTmdbId($id);
                $watchlist->setMediaType($type);
                $watchlist->setTitle($details['title'] ?? $details['name'] ?? 'Inconnu');
                $watchlist->setPosterPath($details['poster_path'] ?? null);
                $watchlist->setAddedAt(new \DateTimeImmutable());
                $em->persist($watchlist);
                $isInWatchlist = true;
            }
        }
        $em->flush();

        // Si c'est une requête Turbo (AJAX), on renvoie le stream pour mettre à jour le bouton
        // On vérifie l'en-tête Accept directement pour être sûr
        if (str_contains($request->headers->get('Accept', ''), 'text/vnd.turbo-stream.html')) {
            $response = $this->render('watchlist/toggle.stream.html.twig', [
                'type' => $type,
                'id' => $id,
                'isInWatchlist' => $isInWatchlist,
                'context' => $request->query->get('context', 'index')
            ]);
            $response->headers->set('Content-Type', 'text/vnd.turbo-stream.html');
            return $response;
        }

        // 2. On recharge la page précédente
        $referer = $request->headers->get('referer') ?? $this->generateUrl('app_home');
        
        // Nettoyage : on enlève les éventuelles anciennes ancres (#...) présentes dans l'URL
        $referer = explode('#', $referer)[0];
        
        // On ajoute l'ancre #media_type_id (ex: #media_movie_550) pour revenir au bon endroit
        return $this->redirect($referer . '#media_' . $type . '_' . $id);
    }

    #[Route('/watchlist/add', name: 'watchlist_add', methods: ['POST'])]
    public function add(Request $request, TmdbService $tmdb, EntityManagerInterface $em, WatchlistRepository $repo): JsonResponse
    {
        // Récupération des données (compatible JSON ou Formulaire)
        $data = $request->toArray(); 
        $tmdbId = $data['media_id'] ?? null;
        $type = $data['media_type'] ?? 'movie';

        if (!$tmdbId) {
            return $this->json(['success' => false, 'message' => 'ID manquant'], 400);
        }

        $user = $this->getUser();

        // Vérifier si déjà dans la liste
        $existing = $repo->findOneBy(['user' => $user, 'tmdbId' => $tmdbId, 'mediaType' => $type]);
        if ($existing) {
            return $this->json(['success' => false, 'message' => 'Déjà dans la watchlist']);
        }

        // Récupérer les infos du film via l'API
        $details = $tmdb->getMediaDetails($tmdbId, $type);
        if (!$details) {
            return $this->json(['success' => false, 'message' => 'Film introuvable'], 404);
        }

        // Créer l'entrée en base
        $watchlist = new Watchlist();
        $watchlist->setUser($user);
        $watchlist->setTmdbId($tmdbId);
        $watchlist->setMediaType($type);
        $watchlist->setTitle($details['title'] ?? $details['name'] ?? 'Titre inconnu');
        $watchlist->setPosterPath($details['poster_path'] ?? null);
        $watchlist->setAddedAt(new \DateTimeImmutable());

        $em->persist($watchlist);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Ajouté avec succès']);
    }

    #[Route('/watchlist/remove', name: 'watchlist_remove', methods: ['POST'])]
    public function remove(Request $request, WatchlistRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        // On gère le format x-www-form-urlencoded envoyé par certains de vos scripts JS
        $tmdbId = $request->request->get('media_id');
        $type = $request->request->get('media_type', 'movie');

        // Si ce n'est pas dans le POST classique, on regarde le JSON
        if (!$tmdbId) {
            $data = $request->toArray();
            $tmdbId = $data['media_id'] ?? null;
            $type = $data['media_type'] ?? 'movie';
        }

        $user = $this->getUser();
        $item = $repo->findOneBy(['user' => $user, 'tmdbId' => $tmdbId, 'mediaType' => $type]);

        if ($item) {
            $em->remove($item);
            $em->flush();
            return $this->json(['success' => true]);
        }

        return $this->json(['success' => false, 'message' => 'Non trouvé']);
    }
}