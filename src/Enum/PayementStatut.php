<?php

namespace App\Enum;

enum PayementStatut: string {
    case NON_PAYE = 'non payé';
    case PAYE = 'payé';
}