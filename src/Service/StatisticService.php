<?php

namespace App\Service;

use App\Repository\CommandeRepository;

class StatisticService {
    private CommandeRepository $commandeRepository;

    public function __construct(CommandeRepository $commandeRepository) {
        $this->commandeRepository = $commandeRepository;
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
}
