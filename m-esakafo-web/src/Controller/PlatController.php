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
    private function configureCorsHeaders(Response $response): Response
    {
        $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:5173');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $response->headers->set('Access-Control-Max-Age', '3600');
        
        return $response;
    }

    #[Route('', name: 'options', methods: ['OPTIONS'])]
    public function options(): Response
    {
        $response = new Response();
        return $this->configureCorsHeaders($response);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(PlatRepository $platRepository): JsonResponse
    {
        $plats = $platRepository->getAllPlats();
        
        $platsArray = array_values(array_filter(array_map(function($plat) {
            if (!$plat || !$plat->getId() || !$plat->getNom()) {
                return null;
            }
            
            return [
                'id' => $plat->getId(),
                'nom' => $plat->getNom(),
                'sprite' => $plat->getSprite() ? trim($plat->getSprite()) : 'brochette.jpg',
                'tempsCuisson' => $plat->getTempsCuisson() ? $plat->getTempsCuisson()->format('H:i:s') : '00:05:00',
                'prix' => $plat->getPrix() ? $plat->getPrix() : '0.00'
            ];
        }, $plats)));

        $response = new JsonResponse($platsArray);
        return $this->configureCorsHeaders($response);
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
        
        return $this->configureCorsHeaders($response);
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
        
        return $this->configureCorsHeaders($response);
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
        
        return $this->configureCorsHeaders($response);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Plat $plat, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($plat);
        $entityManager->flush();

        $response = $this->json(null, Response::HTTP_NO_CONTENT);
        
        return $this->configureCorsHeaders($response);
    }
}
