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
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/plats', name: 'api_plats_')]
class PlatController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer
    ) {}

    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(PlatRepository $platRepository): JsonResponse
    {
        $plats = $platRepository->getAllPlats();
        $json = $this->serializer->serialize($plats, 'json', ['groups' => ['plat:read']]);
        
        $response = new JsonResponse($json, Response::HTTP_OK, [], true);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        
        return $response;
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(Plat $plat): JsonResponse
    {
        $json = $this->serializer->serialize($plat, 'json', ['groups' => ['plat:read']]);
        
        $response = new JsonResponse($json, Response::HTTP_OK, [], true);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        
        return $response;
    }

    #[Route('/', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $plat = new Plat();
        $plat->setNom($data['nom']);
        $plat->setSprite($data['sprite']);
        if (isset($data['temps_cuisson'])) {
            $plat->setTempsCuisson(new \DateTime($data['temps_cuisson']));
        }
        $plat->setPrix($data['prix']);

        $entityManager->persist($plat);
        $entityManager->flush();

        $json = $this->serializer->serialize($plat, 'json', ['groups' => ['plat:read']]);
        
        $response = new JsonResponse($json, Response::HTTP_CREATED, [], true);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        
        return $response;
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

        $json = $this->serializer->serialize($plat, 'json', ['groups' => ['plat:read']]);
        
        $response = new JsonResponse($json, Response::HTTP_OK, [], true);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        
        return $response;
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Plat $plat, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($plat);
        $entityManager->flush();

        $response = new JsonResponse(null, Response::HTTP_NO_CONTENT);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        
        return $response;
    }
}
