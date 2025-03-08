<?php

namespace App\Entity;

use App\Repository\JornadaPartidoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JornadaPartidoRepository::class)]
class JornadaPartido
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Jornada::class)]
    private Jornada $jornada;

    #[ORM\ManyToOne(targetEntity: Partido::class)]
    private Partido $partido;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJornada(): Jornada
    {
        return $this->jornada;
    }

    public function setJornada(Jornada $jornada): JornadaPartido
    {
        $this->jornada = $jornada;
        return $this;
    }

    public function getPartido(): Partido
    {
        return $this->partido;
    }

    public function setPartido(Partido $partido): JornadaPartido
    {
        $this->partido = $partido;
        return $this;
    }

}
