<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class FirebaseService
{
    private $httpClient;
    private $apiKey;
    private $projectId;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = 'AIzaSyApxACHC_7yMd7QfVbmTUUDzmsSCrHdxXI';
        $this->projectId = 'e-sakafo-9db36';
    }

    public function listAllUsers()
    {
        try {
            // Utiliser l'API REST de Firebase pour obtenir un token d'accès
            $response = $this->httpClient->request(
                'POST',
                'https://identitytoolkit.googleapis.com/v1/accounts:signInWithCustomToken',
                [
                    'query' => [
                        'key' => $this->apiKey
                    ],
                    'json' => [
                        'returnSecureToken' => true
                    ]
                ]
            );

            $data = $response->toArray();
            $idToken = $data['idToken'] ?? null;

            if (!$idToken) {
                throw new \Exception('Impossible d\'obtenir le token d\'accès');
            }

            // Utiliser le token pour obtenir la liste des utilisateurs
            $usersResponse = $this->httpClient->request(
                'GET',
                sprintf('https://identitytoolkit.googleapis.com/v1/projects/%s/accounts', $this->projectId),
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $idToken
                    ]
                ]
            );

            $usersData = $usersResponse->toArray();
            $users = $usersData['users'] ?? [];

            // Formater les données des utilisateurs
            return array_map(function($user) {
                return [
                    'uid' => $user['localId'] ?? null,
                    'email' => $user['email'] ?? null,
                    'displayName' => $user['displayName'] ?? null,
                    'phoneNumber' => $user['phoneNumber'] ?? null,
                    'photoUrl' => $user['photoUrl'] ?? null,
                    'emailVerified' => $user['emailVerified'] ?? false,
                    'disabled' => $user['disabled'] ?? false,
                    'creationTime' => $user['createdAt'] ?? null,
                    'lastSignInTime' => $user['lastLoginAt'] ?? null
                ];
            }, $users);

        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la récupération des utilisateurs: ' . $e->getMessage());
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
