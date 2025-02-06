<?php

namespace App\Repository;

use App\Entity\Recette;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recette>
 *
 * @method Recette|null find($id, $lockMode = null, $lockVersion = null)
 * @method Recette|null findOneBy(array $criteria, array $orderBy = null)
 * @method Recette[]    findAll()
 * @method Recette[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecetteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recette::class);
    }

    public function save(Recette $recette): void
    {
        $this->getEntityManager()->persist($recette);
        $this->getEntityManager()->flush();
    }

    public function findByPlatId(int $platId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.plat = :platId')
            ->setParameter('platId', $platId)
            ->getQuery()
            ->getResult();
    }
}
