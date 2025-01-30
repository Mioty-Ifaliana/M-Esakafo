<?php

namespace App\Entity;

use App\Repository\MouvementsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MouvementsRepository::class)]
class Mouvements
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ingredients $ingredient = null;

    #[ORM\Column(nullable: true)]
    private ?int $entre = null;

    #[ORM\Column(nullable: true)]
    private ?int $sortie = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

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

    public function getEntre(): ?int
    {
        return $this->entre;
    }

    public function setEntre(?int $entre): static
    {
        $this->entre = $entre;

        return $this;
    }

    public function getSortie(): ?int
    {
        return $this->sortie;
    }

    public function setSortie(?int $sortie): static
    {
        $this->sortie = $sortie;

        return $this;
    }
}
