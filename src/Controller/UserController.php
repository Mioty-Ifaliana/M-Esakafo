<?php

namespace App\Controller;

use App\Service\FirebaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users')]
class UserController extends AbstractController
{
    private $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    #[Route('/login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['email']) || !isset($data['password'])) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Email et mot de passe requis'
                ], Response::HTTP_BAD_REQUEST);
            }

            $result = $this->firebaseService->signIn($data['email'], $data['password']);

            return $this->json([
                'status' => 'success',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        }
    }

    #[Route('', methods: ['GET'])]
    public function listUsers(Request $request): JsonResponse
    {
        try {
            $token = $request->headers->get('Authorization');
            
            if (!$token) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Token manquant'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Enlever "Bearer " si présent
            $token = str_replace('Bearer ', '', $token);

            $users = $this->firebaseService->listUsers($token);

            return $this->json([
                'status' => 'success',
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
