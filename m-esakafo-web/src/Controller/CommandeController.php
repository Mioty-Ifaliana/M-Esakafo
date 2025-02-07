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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\FirebaseService;
use App\Controller\CorsHeadersTrait;

#[Route('/api/commandes')]
class CommandeController extends AbstractController
{
    use CorsHeadersTrait;

    private $logger;
    private $platRepository;
    private $recetteRepository;
    private $mouvementRepository;
    private $entityManager;
    private $firebaseService;
    private $commandeRepository;

    public function __construct(
        LoggerInterface $logger,
        PlatRepository $platRepository,
        RecetteRepository $recetteRepository,
        MouvementRepository $mouvementRepository,
        EntityManagerInterface $entityManager,
        FirebaseService $firebaseService,
        CommandeRepository $commandeRepository
    ) {
        $this->logger = $logger;
        $this->platRepository = $platRepository;
        $this->recetteRepository = $recetteRepository;
        $this->mouvementRepository = $mouvementRepository;
        $this->entityManager = $entityManager;
        $this->firebaseService = $firebaseService;
        $this->commandeRepository = $commandeRepository;
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
        if (!$commande) {
            return [];
        }

        $plat = $commande->getPlat();
        return [
            'id' => $commande->getId(),
            'userId' => $commande->getUserId(),
            'plat' => [
                'id' => $plat ? $plat->getId() : null,
                'nom' => $plat ? $plat->getNom() : null,
                'sprite' => $plat ? $plat->getSprite() : null,
                'tempsCuisson' => $plat && $plat->getTempsCuisson() ? $plat->getTempsCuisson()->format('H:i:s') : null,
            ],
            'quantite' => $commande->getQuantite(),
            'numeroTicket' => $commande->getNumeroTicket(),
            'statut' => $commande->getStatut(),
            'date_commande' => $commande->getDateCommande() ? $commande->getDateCommande()->format('Y-m-d H:i:s') : null
        ];
    }

    #[Route('/attente', name: 'api_commandes_attente', methods: ['GET'])]
    public function getPendingCommands(CommandeRepository $commandeRepository): JsonResponse
    {
        try {
            $this->logger->info('Fetching pending commands...');
            
            $commandes = $commandeRepository->findPendingCommands();
            
            if (!is_array($commandes) && !$commandes instanceof \Traversable) {
                throw new \RuntimeException('Invalid response from repository');
            }

            $formattedCommandes = array_map(
                [$this, 'formatCommandeDetails'],
                is_array($commandes) ? $commandes : iterator_to_array($commandes)
            );

            $this->logger->info('Successfully fetched pending commands', [
                'count' => count($formattedCommandes)
            ]);

            $response = new JsonResponse($formattedCommandes);
            return $this->addCorsHeaders($response);

        } catch (\Exception $e) {
            $this->logger->error('Error fetching pending orders: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            $response = new JsonResponse([
                'error' => 'An error occurred while fetching pending orders',
                'message' => $e->getMessage()
            ], 500);

            return $this->addCorsHeaders($response);
        }
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

    #[Route('/ventes-totales', name: 'api_commandes_ventes_totales', methods: ['GET'])]
    public function getVentesTotales(CommandeRepository $commandeRepository): JsonResponse
    {
        try {
            // Récupérer les ventes par plat
            $ventesParPlat = $commandeRepository->getTotalVentesParPlat();
            
            // Récupérer les totaux globaux
            $totauxGlobaux = $commandeRepository->getTotalGlobal();
            
            // Formater les données pour inclure des statistiques supplémentaires
            $ventesFormatees = array_map(function($vente) use ($totauxGlobaux) {
                return [
                    'plat' => [
                        'id' => $vente['platId'],
                        'nom' => $vente['platNom'],
                        'prix_unitaire' => (float)$vente['platPrix']
                    ],
                    'statistiques' => [
                        'quantite_vendue' => (int)$vente['totalQuantite'],
                        'montant_total' => (float)$vente['totalVentes'],
                        'pourcentage_total' => $totauxGlobaux['total_ventes'] > 0 
                            ? round(($vente['totalVentes'] / $totauxGlobaux['total_ventes']) * 100, 2)
                            : 0
                    ]
                ];
            }, $ventesParPlat);
            
            $response = $this->json([
                'success' => true,
                'data' => [
                    'ventes_par_plat' => $ventesFormatees,
                    'resume_global' => [
                        'chiffre_affaires_total' => $totauxGlobaux['total_ventes'],
                        'nombre_total_plats_vendus' => $totauxGlobaux['total_quantite'],
                        'nombre_plats_differents' => $totauxGlobaux['nombre_plats'],
                        'moyenne_vente_par_plat' => $totauxGlobaux['nombre_plats'] > 0 
                            ? round($totauxGlobaux['total_ventes'] / $totauxGlobaux['nombre_plats'], 2)
                            : 0
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du calcul des ventes:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $response = $this->json([
                'success' => false,
                'error' => 'Erreur lors du calcul des ventes',
                'message' => $e->getMessage()
            ], 500);
        }
        
        return $this->corsResponse($response);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $commandes = $this->commandeRepository->findAllCommandes();
            
            return $this->json([
                'status' => 'success',
                'data' => $commandes
            ], Response::HTTP_OK, [], ['groups' => ['commande:read']]);
            
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'api_commandes_options', methods: ['OPTIONS'])]
    public function options(): Response
    {
        return $this->handleOptionsRequest();
    }

    #[Route('/{id}', name: 'api_commande_options', methods: ['OPTIONS'])]
    public function optionsId(): Response
    {
        return $this->handleOptionsRequest();
    }

    #[Route('', name: 'api_commandes_create', methods: ['POST', 'OPTIONS'])]
    public function create(Request $request, CommandeRepository $commandeRepository): JsonResponse
    {
        // Gérer la requête OPTIONS
        if ($request->getMethod() === 'OPTIONS') {
            $response = new JsonResponse(null, 204);
            return $this->corsResponse($response);
        }

        try {
            $data = json_decode($request->getContent(), true);
            
            // Log des données reçues
            $this->logger->info('Données reçues:', ['data' => $data]);
            
            // Validation des champs requis
            if (!isset($data['userId']) || !isset($data['platId']) || !isset($data['quantite']) || !isset($data['numeroTicket'])) {
                $response = $this->json([
                    'success' => false,
                    'error' => 'Missing required fields',
                    'required' => ['userId', 'platId', 'quantite', 'numeroTicket'],
                    'received' => $data
                ], 400);
                return $this->corsResponse($response);
            }

            // Validation de la quantité
            if (!is_numeric($data['quantite']) || $data['quantite'] <= 0) {
                $response = $this->json([
                    'success' => false,
                    'error' => 'La quantité doit être un nombre positif'
                ], 400);
                return $this->corsResponse($response);
            }

            // Vérifier que le plat existe
            $plat = $this->platRepository->find($data['platId']);
            if (!$plat) {
                $response = $this->json([
                    'success' => false,
                    'error' => 'Plat non trouvé',
                    'platId' => $data['platId']
                ], 404);
                return $this->corsResponse($response);
            }

            // Vérifier que le numéro de ticket est au bon format (5 caractères)
            if (strlen($data['numeroTicket']) !== 5) {
                $response = $this->json([
                    'success' => false,
                    'error' => 'Le numéro de ticket doit contenir exactement 5 caractères'
                ], 400);
                return $this->corsResponse($response);
            }

            try {
                // Créer les mouvements de sortie des ingrédients
                $this->createSortieIngredients($data['platId'], $data['quantite']);
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de la création des mouvements:', [
                    'platId' => $data['platId'],
                    'quantite' => $data['quantite'],
                    'error' => $e->getMessage()
                ]);
                
                $response = $this->json([
                    'success' => false,
                    'error' => 'Erreur lors de la création des mouvements',
                    'message' => $e->getMessage()
                ], 400);
                return $this->corsResponse($response);
            }

            // Créer la commande
            $commande = $commandeRepository->createNewCommande(
                $data['userId'],
                $data['platId'],
                $data['quantite'],
                $data['numeroTicket']
            );
            
            // Log du succès
            $this->logger->info('Commande créée avec succès:', [
                'commandeId' => $commande->getId(),
                'userId' => $data['userId'],
                'platId' => $data['platId']
            ]);
            
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
                'message' => $e->getMessage(),
                'details' => $this->isDev() ? $e->getTrace() : null
            ], 500);
        }

        return $this->corsResponse($response);
    }

    private function corsResponse(JsonResponse $response): JsonResponse
    {
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE, PATCH');
        $response->headers->set('Access-Control-Allow-Headers', '*');
        $response->headers->set('Access-Control-Expose-Headers', '*');
        $response->headers->set('Access-Control-Max-Age', '3600');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Vary', 'Origin');
        return $response;
    }

    private function addCorsHeaders(JsonResponse $response): JsonResponse
    {
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Max-Age', '3600');
        return $response;
    }

    private function handleOptionsRequest(): Response
    {
        $response = new Response();
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Max-Age', '3600');
        return $response;
    }

    private function isDev(): bool
    {
        return in_array($this->getParameter('kernel.environment'), ['dev', 'test']);
    }
}
