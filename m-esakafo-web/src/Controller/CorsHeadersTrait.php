<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait CorsHeadersTrait
{
    protected function addCorsHeaders(JsonResponse $response): JsonResponse
    {
        // Permettre toutes les origines
        $response->headers->set('Access-Control-Allow-Origin', '*');
        
        // Permettre toutes les méthodes
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE, PATCH');
        
        // Permettre tous les en-têtes
        $response->headers->set('Access-Control-Allow-Headers', '*');
        
        // Cache pour les requêtes préliminaires
        $response->headers->set('Access-Control-Max-Age', '3600');
        
        // Exposer tous les en-têtes à l'application cliente
        $response->headers->set('Access-Control-Expose-Headers', '*');
        
        // En-têtes supplémentaires pour la compatibilité
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        
        return $response;
    }

    protected function handleOptionsRequest(): Response
    {
        $response = new Response();
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE, PATCH');
        $response->headers->set('Access-Control-Allow-Headers', '*');
        $response->headers->set('Access-Control-Max-Age', '3600');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Expose-Headers', '*');
        
        return $response;
    }
}
