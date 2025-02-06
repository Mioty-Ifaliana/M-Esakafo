<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class FirebaseService
{
    private $httpClient;
    private $apiKey;
    private $baseUrl;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = 'AIzaSyApxACHC_7yMd7QfVbmTUUDzmsSCrHdxXI';
        $this->baseUrl = 'https://identitytoolkit.googleapis.com/v1';
    }

    public function listUsers()
    {
        try {
            $response = $this->httpClient->request('POST', 'https://identitytoolkit.googleapis.com/v1/accounts:lookup', [
                'query' => [
                    'key' => $this->apiKey
                ]
            ]);

            $data = $response->toArray();
            return $data['users'] ?? [];
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la récupération des utilisateurs: ' . $e->getMessage());
        }
    }

    public function signIn(string $email, string $password)
    {
        try {
            $response = $this->httpClient->request('POST', "{$this->baseUrl}/accounts:signInWithPassword", [
                'query' => [
                    'key' => $this->apiKey
                ],
                'json' => [
                    'email' => $email,
                    'password' => $password,
                    'returnSecureToken' => true
                ]
            ]);

            return $response->toArray();
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de la connexion: ' . $e->getMessage());
        }
    }
}
