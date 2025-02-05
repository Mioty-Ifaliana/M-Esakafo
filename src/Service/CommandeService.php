<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\Plat;
use App\Entity\User;
use App\Entity\PayementCommande;
use App\Enum\OrderStatut;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;

class CommandeService {
    private CommandeRepository $commandeRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        CommandeRepository $commandeRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->commandeRepository = $commandeRepository;
        $this->entityManager = $entityManager;
    }

    public function calculerStatistiquesVentes(): array {
        $commandes = $this->commandeRepository->getAllCommande();

        $totalVentes = 0;
        $nombreCommandes = count($commandes);
        $platsCommandes = [];

        foreach ($commandes as $commande) {
            $totalVentes += $commande->getPlat()->getPrix() * $commande->getQuantitePlat();
            $platId = $commande->getPlat()->getId();
            if (!isset($platsCommandes[$platId])) {
                $platsCommandes[$platId] = 0;
            }
            $platsCommandes[$platId] += $commande->getQuantitePlat();
        }

        arsort($platsCommandes);
        $platLePlusCommande = key($platsCommandes);

        return [
            'totalVentes' => $totalVentes,
            'nombreCommandes' => $nombreCommandes,
            'platLePlusCommande' => $platLePlusCommande,
        ];
    }

    private function genererNumeroTicket(): int {
        // Récupérer le dernier numéro de ticket
        $derniereCommande = $this->commandeRepository->findOneBy([], ['numero_ticket' => 'DESC']);
        
        if ($derniereCommande) {
            // Si une commande existe, incrémenter le dernier numéro
            return $derniereCommande->getNumeroTicket() + 1;
        }
        
        // Si aucune commande n'existe, commencer à 1000
        return 1000;
    }
}
