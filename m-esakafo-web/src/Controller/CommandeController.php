<?php

namespace App\Controller;

use App\Entity\Mouvement;
use App\Repository\CommandeRepository;
use App\Repository\PlatRepository;
use App\Repository\RecetteRepository;
use App\Repository\MouvementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\FirebaseService;

#[Route('/api/commandes')]
class CommandeController extends AbstractController
{
    private $logger;
    private $platRepository;
    private $recetteRepository;
    private $mouvementRepository;
    private $entityManager;
    private $firebaseService;

    public function __construct(
        LoggerInterface $logger,
        PlatRepository $platRepository,
        RecetteRepository $recetteRepository,
        MouvementRepository $mouvementRepository,
        EntityManagerInterface $entityManager,
        FirebaseService $firebaseService
    ) {
        $this->logger = $logger;
        $this->platRepository = $platRepository;
        $this->recetteRepository = $recetteRepository;
        $this->mouvementRepository = $mouvementRepository;
        $this->entityManager = $entityManager;
        $this->firebaseService = $firebaseService;
    }

    private function createSortieIngredients(int $platId, int $quantiteCommande): void
    {
        // Récupérer la recette du plat
        $plat = $this->platRepository->find($platId);
        if (!$plat) {
            throw new \Exception("Plat non trouvé");
        }

        $recettes = $this->recetteRepository->findBy(['plat' => $plat]);
        
        if (empty($recettes)) {
            throw new \Exception("Aucune recette trouvée pour ce plat");
        }
        
        foreach ($recettes as $recette) {
            // Calculer la quantité totale nécessaire
            $quantiteTotale = $recette->getQuantite() * $quantiteCommande;
            
            // Vérifier le stock disponible
            $stockActuel = $this->mouvementRepository->getStockActuel($recette->getIngredient()->getId());
            if ($stockActuel < $quantiteTotale) {
                throw new \Exception("Stock insuffisant pour l'ingrédient " . $recette->getIngredient()->getNom());
            }
            
            // Créer le mouvement de sortie
            $mouvement = new Mouvement();
            $mouvement->setIngredient($recette->getIngredient())
                     ->setSortie($quantiteTotale)
                     ->setDateMouvement(new \DateTime());
            
            $this->entityManager->persist($mouvement);
        }
        
        $this->entityManager->flush();
    }

    private function formatCommandeDetails($commande): array
    {
        $plat = $this->platRepository->find($commande->getPlatId());
        
        return [
            'id' => $commande->getId(),
            'userId' => $commande->getUserId(),
            'plat' => $plat ? [
                'id' => $plat->getId(),
                'nom' => $plat->getNom(),
                'sprite' => $plat->getSprite(),
                'prix' => $plat->getPrix(),
                'tempsCuisson' => $plat->getTempsCuisson() ? $plat->getTempsCuisson()->format('H:i:s') : null
            ] : null,
            'quantite' => $commande->getQuantite(),
            'numero_ticket' => $commande->getNumeroTicket(),
            'statut' => $commande->getStatut(),
            'date_commande' => $commande->getDateCommande()->format('Y-m-d H:i:s')
        ];
    }

    #[Route('/attente', name: 'api_commandes_attente', methods: ['GET'])]
    public function getPendingCommands(CommandeRepository $commandeRepository): JsonResponse
    {
        try {
            $commandes = $commandeRepository->findPendingCommands();
            $response = $this->json(array_map(
                [$this, 'formatCommandeDetails'],
                $commandes
            ));
        } catch (\Exception $e) {
            $this->logger->error('Error fetching pending orders: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            $response = $this->json([
                'error' => 'An error occurred while fetching pending orders',
                'message' => $e->getMessage()
            ], 500);
        }

        return $this->addCorsHeaders($response);
    }

    #[Route('/check-user-orders/{uid}', name: 'api_check_user_orders', methods: ['GET'])]
    public function checkUserOrders(string $uid, CommandeRepository $commandeRepository): JsonResponse
    {
        try {
            // Récupérer les commandes terminées de l'utilisateur
            $commandes = $commandeRepository->findBy([
                'userId' => $uid,
                'statut' => 1
            ]);

            $results = [];
            foreach ($commandes as $commande) {
                $plat = $this->platRepository->find($commande->getPlatId());
                if ($plat) {
                    $results[] = [
                        'message' => sprintf('Le plat : %s est terminé', $plat->getNom())
                    ];
                }
            }

            return $this->json($results);

        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('', name: 'api_commandes_options', methods: ['OPTIONS'])]
    public function options(): JsonResponse
    {
        $response = new JsonResponse(['status' => 'ok']);
        return $this->addCorsHeaders($response);
    }

    #[Route('', name: 'api_commandes_create', methods: ['POST'])]
    public function create(Request $request, CommandeRepository $commandeRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            // Log des données reçues
            $this->logger->info('Données reçues:', ['data' => $data]);
            
            if (!isset($data['userId']) || !isset($data['platId']) || !isset($data['quantite']) || !isset($data['numeroTicket'])) {
                return $this->json([
                    'success' => false,
                    'error' => 'Missing required fields',
                    'required' => ['userId', 'platId', 'quantite', 'numeroTicket'],
                    'received' => $data
                ], 400);
            }

            // Vérifier que le plat existe
            $plat = $this->platRepository->find($data['platId']);
            if (!$plat) {
                return $this->json([
                    'success' => false,
                    'error' => 'Plat non trouvé',
                    'platId' => $data['platId']
                ], 404);
            }

            // Vérifier que le numéro de ticket est au bon format (5 caractères)
            if (strlen($data['numeroTicket']) !== 5) {
                return $this->json([
                    'success' => false,
                    'error' => 'Le numéro de ticket doit contenir exactement 5 caractères'
                ], 400);
            }

            try {
                // Créer les mouvements de sortie des ingrédients
                $this->createSortieIngredients($data['platId'], $data['quantite']);
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false,
                    'error' => 'Erreur lors de la création des mouvements',
                    'message' => $e->getMessage()
                ], 400);
            }

            // Créer la commande
            $commande = $commandeRepository->createNewCommande(
                $data['userId'],
                $data['platId'],
                $data['quantite'],
                $data['numeroTicket']
            );
            
            $response = $this->json([
                'success' => true,
                'data' => $this->formatCommandeDetails($commande)
            ], 201);
            
        } catch (\Exception $e) {
            $this->logger->error('Error creating order: ' . $e->getMessage(), [
                'exception' => $e,
                'data' => $data ?? null,
            ]);
            $response = $this->json([
                'success' => false,
                'error' => 'An error occurred while creating the order',
                'message' => $e->getMessage()
            ], 500);
        }

        return $this->addCorsHeaders($response);
    }

    private function addCorsHeaders(JsonResponse $response): JsonResponse
    {
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Max-Age', '3600');
        return $response;
    }
}
