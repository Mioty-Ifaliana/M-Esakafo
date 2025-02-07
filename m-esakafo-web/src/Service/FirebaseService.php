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
            error_log('Tentative de connexion admin...');
            
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
            
            if (!isset($signInData['idToken'])) {
                error_log('Échec de l\'authentification. Réponse reçue : ' . json_encode($signInData));
                throw new \Exception('Échec de l\'authentification : ' . (isset($signInData['error']['message']) ? $signInData['error']['message'] : 'Token non reçu'));
            }

            error_log('Authentification réussie, récupération des utilisateurs...');
            
            // Utiliser le token pour obtenir la liste des utilisateurs
            $response = $this->httpClient->request(
                'POST',
                'https://identitytoolkit.googleapis.com/v1/accounts:lookup',
                [
                    'query' => [
                        'key' => $this->apiKey
                    ],
                    'json' => [
                        'idToken' => $signInData['idToken']
                    ]
                ]
            );

            $data = $response->toArray();
            
            if (!isset($data['users'])) {
                error_log('Pas d\'utilisateurs trouvés. Réponse reçue : ' . json_encode($data));
                throw new \Exception('Aucun utilisateur trouvé' . (isset($data['error']['message']) ? ' : ' . $data['error']['message'] : ''));
            }

            error_log('Utilisateurs récupérés avec succès. Nombre d\'utilisateurs : ' . count($data['users']));
            
            return array_map(function($user) {
                return [
                    'uid' => $user['localId'] ?? null,
                    'email' => $user['email'] ?? null,
                    'displayName' => $user['displayName'] ?? null,
                    'emailVerified' => $user['emailVerified'] ?? false,
                    'lastLoginAt' => $user['lastLoginAt'] ?? null,
                    'createdAt' => $user['createdAt'] ?? null
                ];
            }, $data['users']);

        } catch (\Exception $e) {
            error_log('Erreur détaillée dans FirebaseService::listAllUsers : ' . $e->getMessage());
            error_log('Trace : ' . $e->getTraceAsString());
            throw $e; // Propager l'erreur avec le message original
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
