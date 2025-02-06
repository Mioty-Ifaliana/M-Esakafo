<?php

namespace App\Controller;

use App\Entity\Mouvement;
use App\Repository\MouvementRepository;
use App\Repository\IngredientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/mouvements')]
class MouvementController extends AbstractController
{
    private function formatMouvementDetails($mouvement): array
    {
        $ingredient = $mouvement->getIngredient();
        $unite = $ingredient->getUnite();
        
        return [
            'id' => $mouvement->getId(),
            'ingredient' => [
                'id' => $ingredient->getId(),
                'nom' => $ingredient->getNom(),
                'sprite' => $ingredient->getSprite(),
                'unite' => [
                    'id' => $unite->getId(),
                    'nom' => $unite->getNom()
                ]
            ],
            'entree' => $mouvement->getEntree(),
            'sortie' => $mouvement->getSortie(),
            'date_mouvement' => $mouvement->getDateMouvement()->format('Y-m-d')
        ];
    }

    private function formatStockDetails($stock): array
    {
        $ingredient = $stock['ingredient'];
        $unite = $ingredient->getUnite();
        
        return [
            'ingredient' => [
                'id' => $ingredient->getId(),
                'nom' => $ingredient->getNom(),
                'sprite' => $ingredient->getSprite(),
                'unite' => [
                    'id' => $unite->getId(),
                    'nom' => $unite->getNom()
                ]
            ],
            'stock_actuel' => $stock['stock_actuel'],
            'total_entrees' => $stock['total_entrees'],
            'total_sorties' => $stock['total_sorties']
        ];
    }

    #[Route('/stocks', name: 'api_mouvements_stocks', methods: ['GET'])]
    public function getAllStocks(MouvementRepository $mouvementRepository): JsonResponse
    {
        try {
            $stocks = $mouvementRepository->getAllStocks();
            $response = $this->json(array_map(
                [$this, 'formatStockDetails'],
                $stocks
            ));
        } catch (\Exception $e) {
            $response = $this->json([
                'error' => 'An error occurred while fetching stocks',
                'message' => $e->getMessage()
            ], 500);
        }

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        
        return $response;
    }

    #[Route('', name: 'api_mouvements_create', methods: ['POST'])]
    public function create(
        Request $request,
        MouvementRepository $mouvementRepository,
        IngredientRepository $ingredientRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['ingredientId']) || !isset($data['dateMouvement']) || 
            (!isset($data['entree']) && !isset($data['sortie']))) {
            $response = $this->json([
                'error' => 'Missing required fields',
                'required' => ['ingredientId', 'dateMouvement', 'entree or sortie']
            ], 400);
        } else {
            try {
                $ingredient = $ingredientRepository->find($data['ingredientId']);
                
                if (!$ingredient) {
                    $response = $this->json([
                        'error' => 'Ingredient not found'
                    ], 404);
                } else {
                    $mouvement = new Mouvement();
                    $mouvement->setIngredient($ingredient)
                             ->setDateMouvement(new \DateTime($data['dateMouvement']));

                    if (isset($data['entree'])) {
                        $mouvement->setEntree($data['entree']);
                    }
                    if (isset($data['sortie'])) {
                        $mouvement->setSortie($data['sortie']);
                    }
                    
                    $mouvementRepository->save($mouvement);
                    
                    $response = $this->json($this->formatMouvementDetails($mouvement), 201);
                }
            } catch (\Exception $e) {
                $response = $this->json([
                    'error' => 'An error occurred while creating the movement',
                    'message' => $e->getMessage()
                ], 500);
            }
        }

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        
        return $response;
    }

    #[Route('/ingredient/{ingredientId}', name: 'api_mouvements_by_ingredient', methods: ['GET'])]
    public function getByIngredient(int $ingredientId, MouvementRepository $mouvementRepository): JsonResponse
    {
        try {
            $mouvements = $mouvementRepository->findByIngredientId($ingredientId);
            $stockActuel = $mouvementRepository->getStockActuel($ingredientId);
            
            $response = $this->json([
                'mouvements' => array_map([$this, 'formatMouvementDetails'], $mouvements),
                'stock_actuel' => $stockActuel
            ]);
        } catch (\Exception $e) {
            $response = $this->json([
                'error' => 'An error occurred while fetching movements',
                'message' => $e->getMessage()
            ], 500);
        }

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        
        return $response;
    }

    #[Route('', name: 'api_mouvements_options', methods: ['OPTIONS'])]
    public function options(): JsonResponse
    {
        $response = new JsonResponse(['status' => 'ok']);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        return $response;
    }
}
