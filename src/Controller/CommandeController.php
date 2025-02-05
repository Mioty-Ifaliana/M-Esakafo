<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Repository\CommandeRepository;
use App\Service\CommandeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/commandes', name: 'api_commandes_')]
class CommandeController extends AbstractController
{
    private CommandeService $commandeService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        CommandeService $commandeService,
        EntityManagerInterface $entityManager
    ) {
        $this->commandeService = $commandeService;
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] Commande $commande
    ): JsonResponse {
        $this->entityManager->persist($commande);
        $this->entityManager->flush();

        return $this->json($commande, Response::HTTP_CREATED);
    }
}