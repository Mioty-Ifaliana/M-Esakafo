<?php

namespace App\Entity;

use App\Repository\RecetteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecetteRepository::class)]
class Recette
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?plat $plat = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ingredients $ingredient = null;

    #[ORM\Column]
    private ?int $quantite_ingredient = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

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

    public function getIngredient(): ?ingredients
    {
        return $this->ingredient;
    }

    public function setIngredient(?ingredients $ingredient): static
    {
        $this->ingredient = $ingredient;

        return $this;
    }

    public function getQuantiteIngredient(): ?int
    {
        return $this->quantite_ingredient;
    }

    public function setQuantiteIngredient(int $quantite_ingredient): static
    {
        $this->quantite_ingredient = $quantite_ingredient;

        return $this;
    }
}
