<?php

namespace App\Service;

use App\Entity\DeletableEntityInterface;
use Doctrine\ORM\EntityManagerInterface;

class DeleteService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Effectue une suppression logique (soft delete)
     */
    public function softDelete(DeletableEntityInterface $entity): void
    {
        $entity->setDeletedAt(new \DateTimeImmutable());
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    /**
     * Effectue une suppression définitive (hard delete)
     */
    public function hardDelete(DeletableEntityInterface $entity): void
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }
}
