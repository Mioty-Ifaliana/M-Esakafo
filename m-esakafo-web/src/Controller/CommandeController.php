<?php

namespace App\Controller;

use App\Repository\CommandeRepository;
use App\Repository\PlatRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/api/commandes')]
class CommandeController extends AbstractController
{
    private $logger;
    private $platRepository;

    public function __construct(LoggerInterface $logger, PlatRepository $platRepository)
    {
        $this->logger = $logger;
        $this->platRepository = $platRepository;
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

    #[Route('', name: 'api_commandes_options', methods: ['OPTIONS'])]
    public function options(): JsonResponse
    {
        $response = new JsonResponse(['status' => 'ok']);
        return $this->addCorsHeaders($response);
    }

    #[Route('', name: 'api_commandes_create', methods: ['POST'])]
    public function create(Request $request, CommandeRepository $commandeRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Vérifier les données requises
        if (!isset($data['userId']) || !isset($data['platId']) || !isset($data['quantite']) || !isset($data['numeroTicket'])) {
            $response = $this->json([
                'error' => 'Missing required fields',
                'required' => ['userId', 'platId', 'quantite', 'numeroTicket']
            ], 400);
        } else {
            try {
                // Vérifier que le numéro de ticket est au bon format (5 caractères)
                if (strlen($data['numeroTicket']) !== 5) {
                    throw new \InvalidArgumentException('Le numéro de ticket doit contenir exactement 5 caractères');
                }

                $commande = $commandeRepository->createNewCommande(
                    $data['userId'],
                    $data['platId'],
                    $data['quantite'],
                    $data['numeroTicket']
                );
                
                $response = $this->json($this->formatCommandeDetails($commande), 201);
                
            } catch (\Exception $e) {
                $this->logger->error('Error creating order: ' . $e->getMessage(), [
                    'exception' => $e,
                    'data' => $data,
                ]);
                $response = $this->json([
                    'error' => 'An error occurred while creating the order',
                    'message' => $e->getMessage()
                ], 500);
            }
        }

        return $this->addCorsHeaders($response);
    }

    private function addCorsHeaders(JsonResponse $response): JsonResponse
    {
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        return $response;
    }
}
