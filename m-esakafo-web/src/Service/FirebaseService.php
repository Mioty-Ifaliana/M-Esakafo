<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class FirebaseService
{
    private $httpClient;
    private $apiKey;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = 'AIzaSyApxACHC_7yMd7QfVbmTUUDzmsSCrHdxXI';
    }

    public function listAllUsers()
    {
        try {
            // D'abord, se connecter en tant qu'admin
            $signInResponse = $this->httpClient->request(
                'POST',
                'https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword',
                [
                    'query' => [
                        'key' => $this->apiKey
                    ],
                    'json' => [
                        'email' => 'admin@gmail.com',
                        'password' => 'admin123',
                        'returnSecureToken' => true
                    ]
                ]
            );

            $signInData = $signInResponse->toArray();
            $idToken = $signInData['idToken'] ?? null;

            if (!$idToken) {
                throw new \Exception('Échec de l\'authentification admin');
            }

            // Utiliser le token pour obtenir la liste des utilisateurs
            $response = $this->httpClient->request(
                'POST',
                'https://identitytoolkit.googleapis.com/v1/accounts:lookup',
                [
                    'query' => [
                        'key' => $this->apiKey
                    ],
                    'json' => [
                        'idToken' => $idToken
                    ]
                ]
            );

            $data = $response->toArray();
            $users = $data['users'] ?? [];

            // Formater la réponse
            return array_map(function($user) {
                return [
                    'uid' => $user['localId'] ?? null,
                    'email' => $user['email'] ?? null,
                    'displayName' => $user['displayName'] ?? null,
                    'emailVerified' => $user['emailVerified'] ?? false,
                    'lastLoginAt' => $user['lastLoginAt'] ?? null,
                    'createdAt' => $user['createdAt'] ?? null
                ];
            }, $users);

        } catch (\Exception $e) {
            // Log l'erreur pour le débogage
            error_log('Firebase Error: ' . $e->getMessage());
            throw new \Exception('Erreur lors de la récupération des utilisateurs');
        }
    }

    public function listUsers($idToken)
    {
        try {
            $response = $this->httpClient->request('POST', 'https://identitytoolkit.googleapis.com/v1/accounts:lookup', [
                'query' => [
                    'key' => $this->apiKey
                ],
                'json' => [
                    'idToken' => $idToken
                ]
            ]);

            $data = $response->toArray();
            return $data['users'] ?? [];
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la récupération des utilisateurs: ' . $e->getMessage());
        }
    }
}
