<?php

namespace App\Controller;

use App\Repository\CommandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/commandes')]
class CommandeController extends AbstractController
{
    #[Route('', name: 'api_commandes_create', methods: ['POST'])]
    public function create(Request $request, CommandeRepository $commandeRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Vérifier les données requises
        if (!isset($data['userId']) || !isset($data['platId']) || !isset($data['quantite'])) {
            return $this->json([
                'error' => 'Missing required fields'
            ], 400);
        }
        
        try {
            $commande = $commandeRepository->createNewCommande(
                $data['userId'],
                $data['platId'],
                $data['quantite']
            );
            
            return $this->json([
                'id' => $commande->getId(),
                'userId' => $commande->getUserId(),
                'platId' => $commande->getPlatId(),
                'quantite' => $commande->getQuantite(),
                'numero_ticket' => $commande->getNumeroTicket(),
                'statut' => $commande->getStatut(),
                'date_commande' => $commande->getDateCommande()->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'An error occurred while creating the order'
            ], 500);
        }
    }
}
