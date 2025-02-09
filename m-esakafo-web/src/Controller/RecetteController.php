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
    private function formatRecetteDetails($recette): array
    {
        $plat = $recette->getPlat();
        $ingredient = $recette->getIngredient();
        $unite = $ingredient->getUnite();
        
        return [
            'id' => $recette->getId(),
            'plat' => [
                'id' => $plat->getId(),
                'nom' => $plat->getNom(),
                'sprite' => $plat->getSprite(),
                'prix' => $plat->getPrix(),
                'tempsCuisson' => $plat->getTempsCuisson() ? $plat->getTempsCuisson() : null
            ],
            'ingredient' => [
                'id' => $ingredient->getId(),
                'nom' => $ingredient->getNom(),
                'sprite' => $ingredient->getSprite(),
                'unite' => [
                    'id' => $unite->getId(),
                    'nom' => $unite->getNom()
                ]
            ],
            'quantite' => $recette->getQuantite()
        ];
    }

    #[Route('', name: 'api_recettes_list', methods: ['GET'])]
    public function list(RecetteRepository $recetteRepository): JsonResponse
    {
        try {
            $recettes = $recetteRepository->findAll();
            $response = $this->json(array_map(
                [$this, 'formatRecetteDetails'],
                $recettes
            ));
        } catch (\Exception $e) {
            $response = $this->json([
                'error' => 'An error occurred while fetching recipes',
                'message' => $e->getMessage()
            ], 500);
        }

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        
        return $response;
    }

    #[Route('', name: 'api_recettes_create', methods: ['POST'])]
    public function create(
        Request $request, 
        RecetteRepository $recetteRepository,
        PlatRepository $platRepository,
        IngredientRepository $ingredientRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
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
                    
                    $response = $this->json($this->formatRecetteDetails($recette));
                }
            } catch (\Exception $e) {
                $response = $this->json([
                    'error' => 'An error occurred while creating the recipe',
                    'message' => $e->getMessage()
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
            $response = $this->json(array_map(
                [$this, 'formatRecetteDetails'],
                $recettes
            ));
        } catch (\Exception $e) {
            $response = $this->json([
                'error' => 'An error occurred while fetching recipes',
                'message' => $e->getMessage()
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

    #[Route('/listV2', name: 'list_recettes', methods: ['GET'])]
    public function listRecettes(RecetteRepository $recetteRepository): JsonResponse
    {
        // Récupérer toutes les recettes
        $recettes = $recetteRepository->findAll();
    
        $result = [];
        foreach ($recettes as $recette) {
            $plat = $recette->getPlat();
            $ingredient = $recette->getIngredient(); // Récupérer un seul ingrédient
    
            // Vérifiez que le plat et l'ingrédient existent
            if ($plat) {
                $platId = $plat->getId();
    
                // Si le plat n'est pas encore dans le résultat, l'ajouter
                if (!isset($result[$platId])) {
                    $result[$platId] = [
                        'id' => $platId,
                        'nom' => $plat->getNom(),
                        'sprite' => $plat->getSprite(),
                        'prix' => $plat->getPrix(),
                        'tempsCuisson' => $plat->getTempsCuisson(),
                        'ingredients' => []
                    ];
                }
    
                if ($ingredient) {
                    $result[$platId]['ingredients'][] = [
                        'id' => $ingredient->getId(),
                        'nom' => $ingredient->getNom(),
                        'sprite' => $ingredient->getSprite(),
                        'unite' => [
                            'id' => $ingredient->getUnite()->getId(),
                            'nom' => $ingredient->getUnite()->getNom(),
                        ],
                        'quantite' => $recette->getQuantite(),
                    ];
                }
            }
        }
    
        $result = array_values($result);
    
        return $this->json($result);
    }

    #[Route('/recettes/plat/{platId}', name: 'get_recettes_by_plat', methods: ['GET'])]
    public function getRecettesByPlatId(int $platId, RecetteRepository $recetteRepository): JsonResponse
    {
        // Récupérer toutes les recettes pour le plat donné
        $recettes = $recetteRepository->findBy(['plat' => $platId]);

        if (empty($recettes)) {
            return $this->json(['status' => 'error', 'message' => 'Aucune recette trouvée pour ce plat'], 404);
        }

        $result = [];
        foreach ($recettes as $recette) {
            $plat = $recette->getPlat();
            $ingredient = $recette->getIngredient(); // Récupérer un seul ingrédient

            if ($plat) {
                $result[] = [
                    'platId' => $plat->getId(),
                    'platNom' => $plat->getNom(),
                    'platSprite' => $plat->getSprite(),
                    'platPrix' => $plat->getPrix(),
                    'platTempsCuisson' => $plat->getTempsCuisson(),
                    'ingredients' => []
                ];

                if ($ingredient) {
                    $result[count($result) - 1]['ingredients'][] = [
                        'id' => $ingredient->getId(),
                        'nom' => $ingredient->getNom(),
                        'sprite' => $ingredient->getSprite(),
                        'unite' => [
                            'id' => $ingredient->getUnite()->getId(),
                            'nom' => $ingredient->getUnite()->getNom(),
                        ],
                        'quantite' => $recette->getQuantite(),
                    ];
                }
            }
        }

        return $this->json($result);
    }
}

