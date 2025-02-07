<?php

namespace App\Entity;

use App\Repository\PlatRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PlatRepository::class)]
#[ORM\Table(name: 'plats')]
class Plat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['plat:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['plat:read'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['plat:read'])]
    private ?string $sprite = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    #[Groups(['plat:read'])]
    private ?\DateTimeInterface $tempsCuisson = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['plat:read'])]
    private ?string $prix = null;

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

    public function setSprite(string $sprite): static
    {
        $this->sprite = $sprite;
        return $this;
    }

    public function getTempsCuisson(): ?\DateTimeInterface
    {
        return $this->tempsCuisson;
    }

    public function setTempsCuisson(?\DateTimeInterface $tempsCuisson): static
    {
        $this->tempsCuisson = $tempsCuisson;
        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;
        return $this;
    }
}
