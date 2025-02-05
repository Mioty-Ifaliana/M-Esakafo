<?php

namespace App\Controller;

use App\Entity\Plat;
use App\Repository\PlatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Requirement\Requirement;



// #[Route('/api/plats', name: 'api_plats_')]
class PlatController extends AbstractController
{
    #[Route('/plat', name: 'list', methods: ['GET'])]
    public function list(PlatRepository $platRepository): Response
    {
        $plats = $platRepository->getAllPlats();
        return $this->render('plats/index.html.twig', [
            'plats' => $plats
        ]);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'] , requirements: ['id' => Requirement::DIGITS])]
    public function get(Plat $plat): JsonResponse
    {
        return $this->json($plat);
    }

    #[Route('/', name: 'create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] Plat $plat, 
        EntityManagerInterface $entityManager): JsonResponse{

        $entityManager->persist($plat);
        $entityManager->flush();

        return $this->json($plat, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, Plat $plat, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['nom'])) {
            $plat->setNom($data['nom']);
        }
        if (isset($data['sprite'])) {
            $plat->setSprite($data['sprite']);
        }
        if (isset($data['temps_cuisson'])) {
            $plat->setTempsCuisson(new \DateTime($data['temps_cuisson']));
        }
        if (isset($data['prix'])) {
            $plat->setPrix($data['prix']);
        }

        $entityManager->flush();

        return $this->json($plat);
    }



    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Plat $plat, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($plat);
        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
