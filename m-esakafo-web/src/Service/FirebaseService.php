<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class FirebaseService
{
    private $httpClient;
    private $apiKey;
    private $customToken;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = 'AIzaSyApxACHC_7yMd7QfVbmTUUDzmsSCrHdxXI';
        // Token généré depuis Firebase Console pour l'admin
        $this->customToken = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJodHRwczovL2lkZW50aXR5dG9vbGtpdC5nb29nbGVhcGlzLmNvbS9nb29nbGUuaWRlbnRpdHkuaWRlbnRpdHl0b29sa2l0LnYxLklkZW50aXR5VG9vbGtpdCIsImlhdCI6MTcwNzMwNDY5MywiZXhwIjoxNzA3MzA4MjkzLCJpc3MiOiJmaXJlYmFzZS1hZG1pbnNkay1qcHlnbkBlLXNha2Fmby05ZGIzNi5pYW0uZ3NlcnZpY2VhY2NvdW50LmNvbSIsInN1YiI6ImZpcmViYXNlLWFkbWluc2RrLWpweWduQGUtc2FrYWZvLTlkYjM2LmlhbS5nc2VydmljZWFjY291bnQuY29tIiwidWlkIjoiYWRtaW4ifQ.sign';
    }

    public function listAllUsers()
    {
        try {
            error_log('Tentative d\'échange du custom token...');
            
            // Échanger le custom token contre un ID token
            $exchangeResponse = $this->httpClient->request(
                'POST',
                'https://identitytoolkit.googleapis.com/v1/accounts:signInWithCustomToken',
                [
                    'query' => [
                        'key' => $this->apiKey
                    ],
                    'json' => [
                        'token' => $this->customToken,
                        'returnSecureToken' => true
                    ]
                ]
            );

            $exchangeData = $exchangeResponse->toArray();
            
            if (!isset($exchangeData['idToken'])) {
                error_log('Échec de l\'échange du token. Réponse : ' . json_encode($exchangeData));
                throw new \Exception('Échec de l\'échange du token : ' . 
                    (isset($exchangeData['error']['message']) ? $exchangeData['error']['message'] : 'Token non reçu'));
            }

            error_log('Token échangé avec succès, récupération des utilisateurs...');
            
            // Utiliser l'ID token pour obtenir la liste des utilisateurs
            $response = $this->httpClient->request(
                'POST',
                'https://identitytoolkit.googleapis.com/v1/accounts:lookup',
                [
                    'query' => [
                        'key' => $this->apiKey
                    ],
                    'json' => [
                        'idToken' => $exchangeData['idToken']
                    ]
                ]
            );

            $data = $response->toArray();
            
            if (!isset($data['users'])) {
                error_log('Pas d\'utilisateurs trouvés. Réponse : ' . json_encode($data));
                throw new \Exception('Aucun utilisateur trouvé' . 
                    (isset($data['error']['message']) ? ' : ' . $data['error']['message'] : ''));
            }

            error_log('Utilisateurs récupérés avec succès. Nombre : ' . count($data['users']));
            
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
            
            // Capturer et formater l'erreur HTTP si c'en est une
            if (strpos($e->getMessage(), 'HTTP/2') !== false) {
                $message = 'Erreur de communication avec Firebase. ';
                if (strpos($e->getMessage(), '400') !== false) {
                    $message .= 'La requête est mal formée. Vérifiez les identifiants et les tokens.';
                } elseif (strpos($e->getMessage(), '401') !== false) {
                    $message .= 'Non autorisé. Vérifiez les permissions et les tokens.';
                } elseif (strpos($e->getMessage(), '403') !== false) {
                    $message .= 'Accès refusé. Vérifiez les règles de sécurité Firebase.';
                }
                throw new \Exception($message);
            }
            
            throw $e;
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
