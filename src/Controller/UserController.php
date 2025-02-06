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

            $users = $this->firebaseService->listUsers();

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

    #[Route('/list-clients', methods: ['GET'])]
    public function listClients(Request $request): JsonResponse
    {
        try {
            $token = $request->headers->get('Authorization');
            
            if (!$token) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Token manquant'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $users = $this->firebaseService->listUsers($token);
            
            // Filtrer pour exclure admin@gmail.com
            $filteredUsers = array_filter($users, function($user) {
                return $user['email'] !== 'admin@gmail.com';
            });

            return $this->json([
                'status' => 'success',
                'data' => array_values($filteredUsers)
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        }
    }

    #[Route('/count-clients', methods: ['GET'])]
    public function countClients(Request $request): JsonResponse
    {
        try {
            $token = $request->headers->get('Authorization');
            
            if (!$token) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Token manquant'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $users = $this->firebaseService->listUsers($token);
            
            // Filtrer pour exclure admin@gmail.com et compter
            $clientCount = count(array_filter($users, function($user) {
                return $user['email'] !== 'admin@gmail.com';
            }));

            return $this->json([
                'status' => 'success',
                'data' => [
                    'total_clients' => $clientCount
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        }
    }
}
