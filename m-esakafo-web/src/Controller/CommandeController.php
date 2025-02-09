<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Mouvement;
use App\Entity\Plat;
use App\Repository\CommandeRepository;
use App\Repository\PlatRepository;
use App\Repository\MouvementRepository;
use App\Repository\RecetteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\FirebaseService;
use App\Controller\CorsHeadersTrait;
use App\Entity\Recette;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Firestore;

#[Route('/api/commandes', name: 'api_commandes_')]
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

    private function formatCommandeDetails($commande): array
    {
        if (!$commande) {
            return [];
        }

        $plat = $commande->getPlat();
        
        return [
            'id' => $commande->getId(),
            'userId' => $commande->getUserId(),
            'plat' => $plat ? [
                'id' => $plat->getId(),
                'nom' => $plat->getNom(),
                'sprite' => $plat->getSprite(),
                'tempsCuisson' => $plat && $plat->getTempsCuisson() ? $plat->getTempsCuisson() : null,
            ] : null,
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
                'statut' => 3
            ]);

            $results = [];
            foreach ($commandes as $commande) {
                $plat = $this->platRepository->find($commande->getPlatId());
                if ($plat) {
                    $results[] = [
                        'message' => sprintf('Le plat : %s - %s est terminé',$commande->getNumeroTicket(), $plat->getNom())
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
            $ventesParPlat = $commandeRepository->getTotalVentesParPlat();
            
            $totauxGlobaux = $commandeRepository->getTotalGlobal();
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

    #[Route('/list', name: 'list', methods: ['GET'])]
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

    #[Route('/status/{status}', name: 'list_by_status', methods: ['GET'])]
    public function listByStatus(int $status): JsonResponse
    {
        try {
            $commandes = $this->commandeRepository->findCommandesByStatus($status);
            
            // Préparer le message en fonction du statut
            $statusMessages = [
                0 => 'en attente',
                1 => 'en cours de préparation',
                2 => 'prêt',
                3 => 'livré',
                4 => 'payé'
            ];
            
            $statusMessage = $statusMessages[$status] ?? 'statut inconnu';
            
            return $this->json([
                'status' => 'success',
                'message' => 'Commandes ' . $statusMessage,
                'data' => [
                    'status' => $status,
                    'status_label' => $statusMessage,
                    'commandes' => $commandes
                ]
            ], Response::HTTP_OK, [], ['groups' => ['commande:read']]);
            
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/options', name: 'api_commandes_options', methods: ['OPTIONS'])]
    public function options(): Response
    {
        return $this->handleOptionsRequest();
    }

    #[Route('/{id}/options', name: 'api_commande_options', methods: ['OPTIONS'])]
    public function optionsId(): Response
    {
        return $this->handleOptionsRequest();
    }

    #[Route('/create', name: 'api_commandes_create', methods: ['POST', 'OPTIONS'])]
    public function create(Request $request, CommandeRepository $commandeRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            // Log incoming data for debugging
            $this->logger->info('Create Commande Request', [
                'data' => $data,
                'request_content' => $request->getContent()
            ]);

            // Validate required fields
            $requiredFields = ['userId', 'platId', 'quantite', 'numeroTicket'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new \Exception("Missing required field: $field");
                }
            }

            $plat = $this->platRepository->find($data['platId']);
            if (!$plat) {
                throw new \Exception("Plat not found with ID: " . $data['platId']);
            }

            $commande = new Commande();
            $commande->setUserId($data['userId'])
                    ->setPlatId($data['platId'])
                    ->setPlat($plat)
                    ->setQuantite($data['quantite'])
                    ->setNumeroTicket($data['numeroTicket'])
                    ->setStatut(0)
                    ->setDateCommande(new \DateTime());

            // Persister la commande
            $this->entityManager->persist($commande);
            
            try {
                // Créer les mouvements de sortie des ingrédients
                $this->createSortieIngredients($plat, $data['quantite']);
                
                // Sauvegarder la commande
                $this->entityManager->flush();
                
                // Log du succès
                $this->logger->info('Commande créée avec succès:', [
                    'commandeId' => $commande->getId(),
                    'platId' => $data['platId'],
                    'quantite' => $data['quantite']
                ]);

                return $this->corsResponse(new JsonResponse([
                    'status' => 'success',
                    'message' => 'Commande créée avec succès',
                    'data' => $this->formatCommandeDetails($commande)
                ], 201));
                
            } catch (\Exception $e) {
                // Rollback en cas d'erreur
                $this->entityManager->clear();
                
                $this->logger->error('Erreur lors de la création des mouvements:', [
                    'platId' => $data['platId'],
                    'quantite' => $data['quantite'],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return $this->corsResponse(new JsonResponse([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'details' => $this->isDev() ? [
                        'trace' => $e->getTraceAsString(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ] : null
                ], 500));
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création de la commande:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->corsResponse(new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
                'details' => $this->isDev() ? [
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500));
        }
    }

    private function createSortieIngredients(Plat $plat, int $quantite): void
    {
        // Récupérer les ingrédients du plat
        $recettes = $this->entityManager->getRepository(Recette::class)->findBy(['plat' => $plat]);
        
        foreach ($recettes as $recette) {
            $ingredient = $recette->getIngredient();
            $quantiteRequise = $recette->getQuantite();

            $stockActuel = $this->mouvementRepository->getStockActuel($ingredient->getId());
            $quantiteSortie = $quantite * $quantiteRequise;
        
            // Vérifier le stock disponible
            if ($stockActuel < $quantiteSortie) {
                $this->logger->error('Stock insuffisant', [
                    'ingredient' => $ingredient->getNom(),
                    'stock_actuel' => $stockActuel,
                    'quantite_requise' => $quantiteSortie
                ]);
                throw new \Exception("Rupture de stock du produit");
            }

            // Créer un mouvement de sortie
            $mouvement = new Mouvement();
            $mouvement->setIngredient($ingredient)
                     ->setSortie($quantiteSortie)
                     ->setDateMouvement(new \DateTime());

            // // Mettre à jour le stock
            // $ingredient->setQuantite($ingredient->getQuantite() - ($quantiteSortie));
            
            $this->entityManager->persist($mouvement);
        }
    }

    // #[Route('/{id}/statut', name: 'update_commande_statut', methods: ['PUT'])]
    // public function updateStatut(Request $request, int $id): JsonResponse
    // {
    //     // Récupérer la commande par ID
    //     $commande = $this->entityManager->getRepository(Commande::class)->find($id);

    //     if (!$commande) {
    //         return $this->json(['status' => 'error', 'message' => 'Commande non trouvée'], 404);
    //     }

    //     // Récupérer le nouveau statut depuis la requête
    //     $data = json_decode($request->getContent(), true);

    //     if (isset($data['statut'])) {
    //         $commande->setStatut($data['statut']); // Assurez-vous que la méthode setStatut existe dans votre entité Commande
    //         $this->entityManager->flush(); // Persist les changements

    //         return $this->json(['status' => 'success', 'message' => 'Statut mis à jour avec succès'], 200);
    //     }

    //     return $this->json(['status' => 'error', 'message' => 'Statut manquant dans la requête'], 400);
    // }





    #[Route('/{id}/statut', name: 'update_commande_statut', methods: ['PUT'])]
    public function updateStatut(Request $request, int $id, LoggerInterface $logger): JsonResponse
    {
        $commande = $this->entityManager->getRepository(Commande::class)->find($id);
        if (!$commande) {
            return $this->json(['status' => 'error', 'message' => 'Commande non trouvée'], 404);
        }
    
        $data = json_decode($request->getContent(), true);
        if (isset($data['statut'])) {
            $commande->setStatut($data['statut']);
            $this->entityManager->flush();
    
            if ($data['statut'] == 3) {
                try {
                    $firebaseCredentialsJson = $_ENV['FIREBASE_CREDENTIALS'];
                    $firebaseCredentials = json_decode($firebaseCredentialsJson, true);
    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $logger->error('JSON decode error: ' . json_last_error_msg());
                        return $this->json(['status' => 'error', 'message' => 'Invalid Firebase credentials'], 500);
                    }
    
                    $logger->info('Firebase credentials: ' . print_r($firebaseCredentials, true));
    
                    $factory = (new Factory)->withServiceAccount($firebaseCredentials);
                    if (!$factory) {
                        $logger->error('Factory could not be created.');
                        return $this->json(['status' => 'error', 'message' => 'Factory creation failed'], 500);
                    }
    
                    $firestore = $factory->createFirestore();
                    if (!$firestore) {
                        $logger->error('Firestore could not be created.');
                        return $this->json(['status' => 'error', 'message' => 'Firestore creation failed'], 500);
                    }
    
                    $database = $firestore->database();
                    if (!$database) {
                        $logger->error('Database could not be accessed.');
                        return $this->json(['status' => 'error', 'message' => 'Database access failed'], 500);
                    }
    
                    $database->collection('notifications')->add([
                        'userId' => $commande->getUserId(),
                        'message' => "Votre commande est prête !",
                        'timestamp' => (new \DateTime())->format('c'),
                    ]);
                    $logger->info('Notification added to Firestore for user ID: ' . $commande->getUserId());
    
                } catch (\Exception $e) {
                    $logger->error('Error inserting into Firestore: ' . $e->getMessage());
                    return $this->json(['status' => 'error', 'message' => 'Erreur lors de l\'insertion dans Firestore'], 500);
                }
            }
    
            return $this->json(['status' => 'success', 'message' => 'Statut mis à jour avec succès'], 200);
        }
    
        return $this->json(['status' => 'error', 'message' => 'Statut manquant dans la requête'], 400);
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
