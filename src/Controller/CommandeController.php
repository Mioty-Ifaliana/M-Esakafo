<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Repository\CommmandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Requirement\Requirement;

class PlatController extends AbstractController
{
    #[Route('/', name: 'create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] Commande $plat, 
        EntityManagerInterface $entityManager): JsonResponse{

        $entityManager->persist($plat);
        $entityManager->flush();

        return $this->json($plat, Response::HTTP_CREATED);
    }
}