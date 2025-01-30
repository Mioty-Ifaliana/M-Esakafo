<?php

namespace App\Entity;

use App\Enum\PayementStatut;
use App\Repository\PayementCommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PayementCommandeRepository::class)]
class PayementCommande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?user $user = null;

    /**
     * @var Collection<int, commande>
     */
    #[ORM\OneToMany(targetEntity: commande::class, mappedBy: 'payementCommande')]
    private Collection $commande;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prix_total = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?modePaiement $mode_paiement = null;

    #[ORM\Column(enumType: PayementStatut::class)]
    private ?PayementStatut $statut = null;

    public function __construct()
    {
        $this->commande = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

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

    /**
     * @return Collection<int, commande>
     */
    public function getCommande(): Collection
    {
        return $this->commande;
    }

    public function addCommande(commande $commande): static
    {
        if (!$this->commande->contains($commande)) {
            $this->commande->add($commande);
            $commande->setPayementCommande($this);
        }

        return $this;
    }

    public function removeCommande(commande $commande): static
    {
        if ($this->commande->removeElement($commande)) {
            // set the owning side to null (unless already changed)
            if ($commande->getPayementCommande() === $this) {
                $commande->setPayementCommande(null);
            }
        }

        return $this;
    }

    public function getPrixTotal(): ?string
    {
        return $this->prix_total;
    }

    public function setPrixTotal(string $prix_total): static
    {
        $this->prix_total = $prix_total;

        return $this;
    }

    public function getModePaiement(): ?modePaiement
    {
        return $this->mode_paiement;
    }

    public function setModePaiement(?modePaiement $mode_paiement): static
    {
        $this->mode_paiement = $mode_paiement;

        return $this;
    }

    public function getStatut(): ?PayementStatut
    {
        return $this->statut;
    }

    public function setStatut(PayementStatut $statut): static
    {
        $this->statut = $statut;

        return $this;
    }
}
