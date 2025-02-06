<?php

namespace App\Controller;

use App\Repository\CommandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/api/commandes')]
class CommandeController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    private function addCorsHeaders(JsonResponse $response): JsonResponse
    {
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        return $response;
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
        if (!isset($data['userId']) || !isset($data['platId']) || !isset($data['quantite'])) {
            $response = $this->json([
                'error' => 'Missing required fields'
            ], 400);
        } else {
            try {
                $commande = $commandeRepository->createNewCommande(
                    $data['userId'],
                    $data['platId'],
                    $data['quantite']
                );
                
                $response = $this->json([
                    'id' => $commande->getId(),
                    'userId' => $commande->getUserId(),
                    'platId' => $commande->getPlatId(),
                    'quantite' => $commande->getQuantite(),
                    'numero_ticket' => $commande->getNumeroTicket(),
                    'statut' => $commande->getStatut(),
                    'date_commande' => $commande->getDateCommande()->format('Y-m-d H:i:s')
                ]);
                
            } catch (\Exception $e) {
                // Log the exception message and details
                $this->logger->error('Error creating order: ' . $e->getMessage(), [
                    'exception' => $e,
                    'data' => $data,
                ]);
                $response = $this->json([
                    'error' => 'An error occurred while creating the order'
                ], 500);
            }
        }

        return $this->addCorsHeaders($response);
    }
}
