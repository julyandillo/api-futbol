<?php

namespace App\Entity;

use App\Repository\JornadaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: JornadaRepository::class)]
class Jornada
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups('list')]
    private ?int $number = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups('list')]
    private ?\DateTimeInterface $dateStart = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups('list')]
    private ?\DateTimeInterface $dateEnd = null;

    #[ORM\ManyToOne(targetEntity: Competicion::class, inversedBy: "jornadas")]
    private Competicion $competicion;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getDateStart(): ?\DateTimeInterface
    {
        return $this->dateStart;
    }

    public function setDateStart(\DateTimeInterface $dateStart): static
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    public function getDateEnd(): ?\DateTimeInterface
    {
        return $this->dateEnd;
    }

    public function setDateEnd(\DateTimeInterface $dateEnd): static
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    public function setCompeticion(Competicion $competicion): static
    {
        $this->competicion = $competicion;
        return $this;
    }

    public function getCompeticion(): Competicion
    {
        return $this->competicion;
    }
}
