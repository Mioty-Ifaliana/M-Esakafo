<?php

namespace App\Entity;

use App\Repository\IngredientRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: IngredientRepository::class)]
class Ingredient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ingredient:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['ingredient:read'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['ingredient:read'])]
    private ?string $sprite = null;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Groups(['ingredient:read'])]
    private ?float $quantite = null;

    #[ORM\ManyToOne(targetEntity: Unite::class)]
    #[ORM\JoinColumn(name: "id_unite", referencedColumnName: "id", nullable: false)]
    #[Groups(['ingredient:read'])]
    private ?Unite $unite = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getSprite(): ?string
    {
        return $this->sprite;
    }

    public function setSprite(?string $sprite): static
    {
        $this->sprite = $sprite;

        return $this;
    }

    public function getQuantite(): ?float
    {
        return $this->quantite;
    }

    public function setQuantite(?float $quantite): static
    {
        $this->quantite = $quantite;
        return $this;
    }

    public function getUnite(): ?Unite
    {
        return $this->unite;
    }

    public function setUnite(?Unite $unite): static
    {
        $this->unite = $unite;

        return $this;
    }
}
