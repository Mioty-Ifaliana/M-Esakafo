<?php

namespace App\Controller;

use App\Entity\Ingredient;
use App\Repository\IngredientRepository;
use App\Repository\UniteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/ingredients')]
class IngredientController extends AbstractController
{
    private $entityManager;
    private $ingredientRepository;
    private $uniteRepository;
    private $serializer;

    public function __construct(
        EntityManagerInterface $entityManager,
        IngredientRepository $ingredientRepository,
        UniteRepository $uniteRepository,
        SerializerInterface $serializer
    ) {
        $this->entityManager = $entityManager;
        $this->ingredientRepository = $ingredientRepository;
        $this->uniteRepository = $uniteRepository;
        $this->serializer = $serializer;
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $ingredients = $this->ingredientRepository->findAllWithUnite();
        
        return $this->json([
            'status' => 'success',
            'data' => $ingredients
        ], Response::HTTP_OK, [], ['groups' => ['ingredient:read']]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function getById(int $id): JsonResponse
    {
        $ingredient = $this->ingredientRepository->find($id);

        if (!$ingredient) {
            return $this->json([
                'status' => 'error',
                'message' => 'Ingredient non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'status' => 'success',
            'data' => $ingredient
        ], Response::HTTP_OK, [], ['groups' => ['ingredient:read']]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['nom']) || !isset($data['id_unite'])) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Les champs nom et id_unite sont requis'
                ], Response::HTTP_BAD_REQUEST);
            }

            $unite = $this->uniteRepository->find($data['id_unite']);
            if (!$unite) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Unité non trouvée'
                ], Response::HTTP_NOT_FOUND);
            }

            $ingredient = new Ingredient();
            $ingredient->setNom($data['nom']);
            $ingredient->setUnite($unite);
            
            if (isset($data['sprite'])) {
                $ingredient->setSprite($data['sprite']);
            }

            $this->entityManager->persist($ingredient);
            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Ingrédient créé avec succès',
                'data' => $ingredient
            ], Response::HTTP_CREATED, [], ['groups' => ['ingredient:read']]);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la création de l\'ingrédient'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $ingredient = $this->ingredientRepository->find($id);

            if (!$ingredient) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Ingrédient non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);

            if (isset($data['nom'])) {
                $ingredient->setNom($data['nom']);
            }

            if (isset($data['id_unite'])) {
                $unite = $this->uniteRepository->find($data['id_unite']);
                if (!$unite) {
                    return $this->json([
                        'status' => 'error',
                        'message' => 'Unité non trouvée'
                    ], Response::HTTP_NOT_FOUND);
                }
                $ingredient->setUnite($unite);
            }

            if (isset($data['sprite'])) {
                $ingredient->setSprite($data['sprite']);
            }

            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Ingrédient mis à jour avec succès',
                'data' => $ingredient
            ], Response::HTTP_OK, [], ['groups' => ['ingredient:read']]);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la mise à jour de l\'ingrédient'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $ingredient = $this->ingredientRepository->find($id);

            if (!$ingredient) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Ingrédient non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($ingredient);
            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'Ingrédient supprimé avec succès'
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la suppression de l\'ingrédient'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
