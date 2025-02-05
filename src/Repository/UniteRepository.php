<?php

namespace App\Repository;

use App\Entity\Unite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Unite>
 *
 * @method Unite|null find($id, $lockMode = null, $lockVersion = null)
 * @method Unite|null findOneBy(array $criteria, array $orderBy = null)
 * @method Unite[]    findAll()
 * @method Unite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UniteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Unite::class);
    }

    public function save(Unite $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Unite $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
