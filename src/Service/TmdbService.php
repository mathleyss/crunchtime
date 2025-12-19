<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TmdbService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.themoviedb.org/3';

    public function __construct(
        private HttpClientInterface $client,
        private CacheInterface $cache,
        string $tmdbApiKey = ''
    ) {
        $this->apiKey = $tmdbApiKey;
    }

    // Méthode générique pour faire les appels API
    private function fetchFromApi(string $endpoint, array $params = []): array
    {
        $response = $this->client->request('GET', $this->baseUrl . $endpoint, [
            'query' => array_merge(['api_key' => $this->apiKey, 'language' => 'fr-FR'], $params)
        ]);
        return $response->toArray();
    }

    public function getTrendingMovies(): array
    {
        return $this->cache->get('trending_movies_week', function (ItemInterface $item) {
            $item->expiresAfter(3600);
            return $this->fetchFromApi('/trending/movie/week')['results'];
        });
    }

    public function getTrendingSeries(): array
    {
        return $this->cache->get('trending_series_week', function (ItemInterface $item) {
            $item->expiresAfter(3600);
            return $this->fetchFromApi('/trending/tv/week')['results'];
        });
    }

    public function getActionMovies(): array
    {
        return $this->cache->get('action_movies', function (ItemInterface $item) {
            $item->expiresAfter(3600);
            return $this->fetchFromApi('/discover/movie', ['with_genres' => 28])['results'];
        });
    }

    public function getTopRatedSeries(): array
    {
        return $this->cache->get('top_rated_series', function (ItemInterface $item) {
            $item->expiresAfter(3600);
            return $this->fetchFromApi('/tv/top_rated')['results'];
        });
    }

    public function getMediaDetails(int $id, string $type = 'movie'): ?array
    {
        return $this->cache->get("media_details_{$type}_{$id}_full", function (ItemInterface $item) use ($id, $type) {
            $item->expiresAfter(3600);
            try {
                // On récupère les détails + les crédits + les providers (streaming) en un seul appel
                return $this->fetchFromApi("/{$type}/{$id}", [
                    'append_to_response' => 'credits,watch/providers'
                ]);
            } catch (\Exception $e) {
                return null;
            }
        });
    }

    public function search(string $query, string $genre = '', string $year = ''): array
    {
        // Pas de cache long pour la recherche
        $endpoint = empty($query) ? '/discover/movie' : '/search/movie';
        $params = [];

        if (!empty($query)) $params['query'] = $query;
        if (!empty($genre)) $params['with_genres'] = $genre;
        if (!empty($year)) $params['primary_release_year'] = $year;

        return $this->fetchFromApi($endpoint, $params);
    }
}
