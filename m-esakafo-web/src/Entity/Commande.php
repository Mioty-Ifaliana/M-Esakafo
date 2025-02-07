<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Plat;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\Table(name: 'commandes')]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['commande:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $userId = null;

    #[ORM\ManyToOne(targetEntity: Plat::class)]
    #[ORM\JoinColumn(name: 'plat_id', referencedColumnName: 'id')]
    private ?Plat $plat = null;

    #[ORM\Column(name: 'plat_id')]
    private ?int $platId = null;

    #[ORM\Column]
    private ?int $quantite = null;

    #[ORM\Column(length: 5)]
    private ?string $numero_ticket = null;

    #[ORM\Column]
    private ?int $statut = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['commande:read'])]
    private ?\DateTimeInterface $date_commande = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

    public function getPlat(): ?Plat
    {
        return $this->plat;
    }

    public function setPlat(?Plat $plat): static
    {
        $this->plat = $plat;
        $this->platId = $plat?->getId();
        return $this;
    }

    public function getPlatId(): ?int
    {
        return $this->platId;
    }

    public function setPlatId(int $platId): static
    {
        $this->platId = $platId;
        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;
        return $this;
    }

    public function getNumeroTicket(): ?string
    {
        return $this->numero_ticket;
    }

    public function setNumeroTicket(string $numero_ticket): static
    {
        $this->numero_ticket = $numero_ticket;
        return $this;
    }

    public function getStatut(): ?int
    {
        return $this->statut;
    }

    public function setStatut(int $statut): static
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
}
