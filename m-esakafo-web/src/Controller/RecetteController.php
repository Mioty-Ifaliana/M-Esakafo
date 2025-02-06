<?php

namespace App\Controller;

use App\Entity\Recette;
use App\Repository\RecetteRepository;
use App\Repository\PlatRepository;
use App\Repository\IngredientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/recettes')]
class RecetteController extends AbstractController
{
    #[Route('', name: 'api_recettes_create', methods: ['POST'])]
    public function create(
        Request $request, 
        RecetteRepository $recetteRepository,
        PlatRepository $platRepository,
        IngredientRepository $ingredientRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        // Vérifier les données requises
        if (!isset($data['platId']) || !isset($data['ingredientId']) || !isset($data['quantite'])) {
            $response = $this->json([
                'error' => 'Missing required fields'
            ], 400);
        } else {
            try {
                $plat = $platRepository->find($data['platId']);
                $ingredient = $ingredientRepository->find($data['ingredientId']);
                
                if (!$plat || !$ingredient) {
                    $response = $this->json([
                        'error' => 'Plat or Ingredient not found'
                    ], 404);
                } else {
                    $recette = new Recette();
                    $recette->setPlat($plat)
                           ->setIngredient($ingredient)
                           ->setQuantite($data['quantite']);
                    
                    $recetteRepository->save($recette);
                    
                    $response = $this->json([
                        'id' => $recette->getId(),
                        'platId' => $recette->getPlat()->getId(),
                        'ingredientId' => $recette->getIngredient()->getId(),
                        'quantite' => $recette->getQuantite()
                    ]);
                }
            } catch (\Exception $e) {
                $response = $this->json([
                    'error' => 'An error occurred while creating the recipe'
                ], 500);
            }
        }

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        
        return $response;
    }

    #[Route('/plat/{platId}', name: 'api_recettes_by_plat', methods: ['GET'])]
    public function getByPlat(int $platId, RecetteRepository $recetteRepository): JsonResponse
    {
        try {
            $recettes = $recetteRepository->findByPlatId($platId);
            $response = $this->json(array_map(function($recette) {
                return [
                    'id' => $recette->getId(),
                    'platId' => $recette->getPlat()->getId(),
                    'ingredientId' => $recette->getIngredient()->getId(),
                    'quantite' => $recette->getQuantite()
                ];
            }, $recettes));
        } catch (\Exception $e) {
            $response = $this->json([
                'error' => 'An error occurred while fetching recipes'
            ], 500);
        }

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        
        return $response;
    }

    #[Route('', name: 'api_recettes_options', methods: ['OPTIONS'])]
    public function options(): JsonResponse
    {
        $response = new JsonResponse(['status' => 'ok']);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        return $response;
    }
}
