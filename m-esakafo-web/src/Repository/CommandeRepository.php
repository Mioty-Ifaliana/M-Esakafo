<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 *
 * @method Commande|null find($id, $lockMode = null, $lockVersion = null)
 * @method Commande|null findOneBy(array $criteria, array $orderBy = null)
 * @method Commande[]    findAll()
 * @method Commande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    public function createNewCommande(string $userId, int $platId, int $quantite): Commande
    {
        $commande = new Commande();
        
        // Générer un numéro de ticket à 5 chiffres
        $numeroTicket = str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        
        // Configurer la commande
        $commande->setUserId($userId)
                ->setPlatId($platId)
                ->setQuantite($quantite)
                ->setNumeroTicket($numeroTicket)
                ->setStatut(0)
                ->setDateCommande(new \DateTime());
        
        // Persister et sauvegarder la commande
        $this->getEntityManager()->persist($commande);
        $this->getEntityManager()->flush();
        
        return $commande;
    }

    public function findPendingCommands(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.statut = :statut')
            ->setParameter('statut', 0)
            ->orderBy('c.dateCommande', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
