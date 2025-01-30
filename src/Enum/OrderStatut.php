<?php

namespace App\Enum;

enum OrderStatut: string {
    case COMMANDE = 'commandé';
    case EN_PREPARATION = 'en preparation';
    case EN_ATTENTE_DE_RECUPERATION = 'en attente de recupération';
    case RECUPERE = 'recupéré';
}