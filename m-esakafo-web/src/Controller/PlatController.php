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

#[Route('/api/plats', name: 'api_plats_')]
class PlatController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(PlatRepository $platRepository): JsonResponse
    {
        try {
            $plats = $platRepository->getAllPlats();

            return $this->json([
                'status' => 'success',
                'data' => $plats
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get($id, PlatRepository $platRepository): JsonResponse
    {
        $plat = $platRepository->find($id);
        
        if (!$plat) {
            return $this->json(['error' => 'Plat not found'], 404);
        }
        
        $response = $this->json([
            'id' => $plat->getId(),
            'nom' => $plat->getNom(),
            'sprite' => $plat->getSprite(),
            'tempsCuisson' => $plat->getTempsCuisson() ? $plat->getTempsCuisson()->format('H:i:s') : null,
            'prix' => $plat->getPrix()
        ]);
        
        // Ajouter les headers CORS
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        
        return $response;
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $plat = new Plat();
        $plat->setNom($data['nom']);
        $plat->setSprite($data['sprite']);
        $plat->setPrix($data['prix']);
        
        if (isset($data['tempsCuisson'])) {
            $tempsCuisson = new \DateTime($data['tempsCuisson']);
            $plat->setTempsCuisson($tempsCuisson);
        }
        
        $entityManager->persist($plat);
        $entityManager->flush();
        
        $response = $this->json([
            'id' => $plat->getId(),
            'nom' => $plat->getNom(),
            'sprite' => $plat->getSprite(),
            'tempsCuisson' => $plat->getTempsCuisson() ? $plat->getTempsCuisson()->format('H:i:s') : null,
            'prix' => $plat->getPrix()
        ], 201);
        
        // Ajouter les headers CORS
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

        $response = $this->json($plat);
        
        // Ajouter les headers CORS
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

        $response = $this->json(null, Response::HTTP_NO_CONTENT);
        
        // Ajouter les headers CORS
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
        
        return $response;
    }
}
