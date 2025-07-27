<?php

namespace App\Entity;

use App\Repository\PartidoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

#[ORM\Entity(repositoryClass: PartidoRepository::class)]
class Partido
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Context([DateTimeNormalizer::FORMAT_KEY => DATE_ATOM])]
    private ?\DateTimeInterface $datetime = null;

    #[ORM\Column(nullable: true)]
    private ?int $goles_local = null;

    #[ORM\Column(nullable: true)]
    private ?int $goles_visitante = null;

    #[ORM\Column]
    private bool $disputado = false;

    #[ORM\ManyToOne(targetEntity: Equipo::class, inversedBy: 'partidos')]
    private ?Equipo $equipoLocal = null;

    #[ORM\ManyToOne(targetEntity: Equipo::class, inversedBy: 'partidos')]
    private ?Equipo $equipoVisitante = null;

    #[ORM\ManyToOne(targetEntity: Estadio::class, inversedBy: 'partidos')]
    private ?Estadio $estadio = null;

    #[Orm\ManyToOne(targetEntity: Arbitro::class, inversedBy: 'partidos')]
    private ?Arbitro $arbitro = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDatetime(): ?\DateTimeInterface
    {
        return $this->datetime;
    }

    public function setDatetime(\DateTimeInterface $datetime): static
    {
        $this->datetime = $datetime;

        return $this;
    }

    public function getGolesLocal(): ?int
    {
        return $this->goles_local;
    }

    public function setGolesLocal(int $goles_local): static
    {
        $this->goles_local = $goles_local;

        return $this;
    }

    public function getGolesVisitante(): ?int
    {
        return $this->goles_visitante;
    }

    public function setGolesVisitante(?int $goles_visitante): static
    {
        $this->goles_visitante = $goles_visitante;

        return $this;
    }

    public function isDisputado(): ?bool
    {
        return $this->disputado;
    }

    public function setDisputado(bool $disputado): static
    {
        $this->disputado = $disputado;

        return $this;
    }

    public function getEquipoLocal(): ?Equipo
    {
        return $this->equipoLocal;
    }

    public function setEquipoLocal(?Equipo $equipoLocal): Partido
    {
        $this->equipoLocal = $equipoLocal;
        return $this;
    }

    public function getEquipoVisitante(): ?Equipo
    {
        return $this->equipoVisitante;
    }

    public function setEquipoVisitante(?Equipo $equipoVisitante): Partido
    {
        $this->equipoVisitante = $equipoVisitante;
        return $this;
    }

    public function getEstadio(): ?Estadio
    {
        return $this->estadio;
    }

    public function setEstadio(?Estadio $estadio): Partido
    {
        $this->estadio = $estadio;
        return $this;
    }

    public function getArbitro(): ?Arbitro
    {
        return $this->arbitro;
    }

    public function setArbitro(?Arbitro $arbitro): Partido
    {
        $this->arbitro = $arbitro;
        return $this;
    }

}
