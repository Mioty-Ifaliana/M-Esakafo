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
        try {
            $plats = $platRepository->getAllPlats();
            
            if (!is_array($plats)) {
                return new JsonResponse([
                    'error' => 'Erreur lors de la récupération des plats'
                ], 500);
            }

            $platsArray = [];
            foreach ($plats as $plat) {
                if ($plat && $plat->getId() && $plat->getNom()) {
                    $platsArray[] = [
                        'id' => $plat->getId(),
                        'nom' => $plat->getNom(),
                        'sprite' => $plat->getSprite() ? trim($plat->getSprite()) : 'brochette.jpg',
                        'tempsCuisson' => $plat->getTempsCuisson() ? $plat->getTempsCuisson()->format('H:i:s') : '00:05:00',
                        'prix' => $plat->getPrix() ? $plat->getPrix() : '0.00'
                    ];
                }
            }

            $response = new JsonResponse($platsArray);
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
            $response->headers->set('Content-Type', 'application/json');
            
            return $response;

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get($id, PlatRepository $platRepository): JsonResponse
    {
        try {
            $plat = $platRepository->find($id);
            
            if (!$plat) {
                return new JsonResponse([
                    'error' => 'Plat not found'
                ], 404);
            }
            
            $response = new JsonResponse([
                'id' => $plat->getId(),
                'nom' => $plat->getNom(),
                'sprite' => $plat->getSprite(),
                'tempsCuisson' => $plat->getTempsCuisson() ? $plat->getTempsCuisson()->format('H:i:s') : null,
                'prix' => $plat->getPrix()
            ]);
            
            return $this->configureCorsHeaders($response);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['nom']) || !isset($data['sprite']) || !isset($data['prix'])) {
                return new JsonResponse([
                    'error' => 'Données incomplètes'
                ], 400);
            }
            
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
            
            $response = new JsonResponse([
                'id' => $plat->getId(),
                'nom' => $plat->getNom(),
                'sprite' => $plat->getSprite(),
                'tempsCuisson' => $plat->getTempsCuisson() ? $plat->getTempsCuisson()->format('H:i:s') : null,
                'prix' => $plat->getPrix()
            ], 201);
            
            return $this->configureCorsHeaders($response);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, Plat $plat, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
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

            $response = new JsonResponse([
                'id' => $plat->getId(),
                'nom' => $plat->getNom(),
                'sprite' => $plat->getSprite(),
                'tempsCuisson' => $plat->getTempsCuisson() ? $plat->getTempsCuisson()->format('H:i:s') : null,
                'prix' => $plat->getPrix()
            ]);
            
            return $this->configureCorsHeaders($response);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Plat $plat, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $entityManager->remove($plat);
            $entityManager->flush();

            $response = new JsonResponse(null, Response::HTTP_NO_CONTENT);
            
            return $this->configureCorsHeaders($response);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
