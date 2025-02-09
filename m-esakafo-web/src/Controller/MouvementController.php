<?php

namespace App\Controller;

use App\Entity\Mouvement;
use App\Entity\Ingredient;
use App\Repository\MouvementRepository;
use App\Repository\IngredientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

#[Route('/api/mouvements')]
class MouvementController extends AbstractController
{
    private $entityManager;
    private $mouvementRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        MouvementRepository $mouvementRepository
    ) {
        $this->entityManager = $entityManager;
        $this->mouvementRepository = $mouvementRepository;
    }

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
    public function getAllStocks(): JsonResponse
    {
        try {
            $stocks = $this->mouvementRepository->getAllStocks();
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
                    
                    $this->mouvementRepository->save($mouvement);
                    
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

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            // Récupérer tous les mouvements
            $mouvements = $this->mouvementRepository->findAllMouvements();
            
            // Préparer les données à retourner
            $data = [];
            foreach ($mouvements as $mouvement) {
                $data[] = [
                    'id' => $mouvement->getId(),
                    'ingredient' => $mouvement->getIngredient() ? $mouvement->getIngredient()->getNom() : null, // Détails de l'ingrédient
                    'entre' => $mouvement->getEntree(),
                    'sortie' => $mouvement->getSortie(),
                    'date' => $mouvement->getDateMouvement()->format('Y-m-d H:i:s'),
                ];
            }
    
            return $this->json([
                'status' => 'success',
                'data' => $data
            ], 200);
            
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/mouvements', name: 'get_all_ingredient_movements', methods: ['GET'])]
    public function getAllIngredientMovements(MouvementRepository $mouvementRepository, IngredientRepository $ingredientRepository): JsonResponse
    {
        // Récupérer tous les ingrédients
        $ingredients = $ingredientRepository->findAll();
        $result = [];
    
        // Initialiser les totaux pour chaque ingrédient
        foreach ($ingredients as $ingredient) {
            $mouvements = $mouvementRepository->findBy(['ingredient' => $ingredient]);
    
            $sommeEntre = 0;
            $sommeSortie = 0;
    
            // Calculer les sommes
            foreach ($mouvements as $mouvement) {
                if ($mouvement->getEntree()) {
                    $sommeEntre += $mouvement->getEntree();
                } elseif ($mouvement->getSortie()) {
                    $sommeSortie += $mouvement->getSortie();
                }
            }
    
            // Calculer le reste en stock
            $resteEnStock = $sommeEntre - $sommeSortie;
    
            // Ajouter les informations à la réponse
            $result[] = [
                'id' => $ingredient->getId(),
                'nom' => $ingredient->getNom(),
                'sprite' => $ingredient->getSprite(),
                'sommeEntre' => $sommeEntre,
                'sommeSortie' => $sommeSortie,
                'resteEnStock' => $resteEnStock,
            ];
        }
    
        return $this->json($result);
    }

    
    #[Route('/ingredient/{ingredientId}', name: 'api_mouvements_by_ingredient', methods: ['GET'])]
    public function getByIngredient(int $ingredientId): JsonResponse
    {
        try {
            $mouvements = $this->mouvementRepository->findByIngredientId($ingredientId);
            $stockActuel = $this->mouvementRepository->getStockActuel($ingredientId);
            
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

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $mouvement = $this->mouvementRepository->find($id);
            
            if (!$mouvement) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Mouvement non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }
            
            $data = json_decode($request->getContent(), true);
            
            if ($data === null) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'JSON invalide'
                ], Response::HTTP_BAD_REQUEST);
            }
            
            // Mise à jour des champs si présents dans la requête
            if (isset($data['entree'])) {
                $mouvement->setEntree($data['entree']);
            }
            
            if (isset($data['sortie'])) {
                $mouvement->setSortie($data['sortie']);
            }
            
            if (isset($data['dateMouvement'])) {
                try {
                    $dateMouvement = new \DateTime($data['dateMouvement']);
                    $mouvement->setDateMouvement($dateMouvement);
                } catch (\Exception $e) {
                    return $this->json([
                        'status' => 'error',
                        'message' => 'Format de date invalide'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }
            
            if (isset($data['ingredient_id'])) {
                $ingredient = $this->entityManager
                    ->getRepository(Ingredient::class)
                    ->find($data['ingredient_id']);
                    
                if (!$ingredient) {
                    return $this->json([
                        'status' => 'error',
                        'message' => 'Ingrédient non trouvé'
                    ], Response::HTTP_NOT_FOUND);
                }
                
                $mouvement->setIngredient($ingredient);
            }
            
            $this->mouvementRepository->save($mouvement, true);
            
            return $this->json([
                'status' => 'success',
                'message' => 'Mouvement mis à jour avec succès',
                'data' => $this->formatMouvementDetails($mouvement)
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
