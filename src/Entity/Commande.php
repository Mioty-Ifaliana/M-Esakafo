<?php

namespace App\Entity;

use App\Enum\OrderStatut;
use App\Repository\CommandeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $numero_ticket = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?user $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?plat $plat = null;

    #[ORM\Column]
    private ?int $quantite_plat = null;

    #[ORM\Column(enumType: OrderStatut::class)]
    private ?OrderStatut $statut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_commande = null;

    #[ORM\ManyToOne(inversedBy: 'commande')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PayementCommande $payementCommande = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getNumeroTicket(): ?int
    {
        return $this->numero_ticket;
    }

    public function setNumeroTicket(int $numero_ticket): static
    {
        $this->numero_ticket = $numero_ticket;

        return $this;
    }

    public function getUser(): ?user
    {
        return $this->user;
    }

    public function setUser(?user $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getPlat(): ?plat
    {
        return $this->plat;
    }

    public function setPlat(?plat $plat): static
    {
        $this->plat = $plat;

        return $this;
    }

    public function getQuantitePlat(): ?int
    {
        return $this->quantite_plat;
    }

    public function setQuantitePlat(int $quantite_plat): static
    {
        $this->quantite_plat = $quantite_plat;

        return $this;
    }

    public function getStatut(): ?OrderStatut
    {
        return $this->statut;
    }

    public function setStatut(OrderStatut $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateCommande(): ?\DateTimeInterface
    {
        return $this->date_commande;
    }

    public function setDateCommande(\DateTimeInterface $date_commande): static
    {
        $this->date_commande = $date_commande;

        return $this;
    }

    public function getPayementCommande(): ?PayementCommande
    {
        return $this->payementCommande;
    }

    public function setPayementCommande(?PayementCommande $payementCommande): static
    {
        $this->payementCommande = $payementCommande;

        return $this;
    }
}
