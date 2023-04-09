<?php

namespace App\Entity;

use App\Repository\PlantillaJugadorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlantillaJugadorRepository::class)]
class PlantillaJugador
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $dorsal = null;

    #[ORM\ManyToOne(targetEntity: Jugador::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private Jugador $jugador;

    #[ORM\ManyToOne(targetEntity: Plantilla::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Plantilla $plantilla;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDorsal(): ?int
    {
        return $this->dorsal;
    }

    public function setDorsal(int $dorsal): self
    {
        $this->dorsal = $dorsal;

        return $this;
    }

    public function getJugador(): Jugador
    {
        return $this->jugador;
    }

    public function setJugador(Jugador $jugador): PlantillaJugador
    {
        $this->jugador = $jugador;
        return $this;
    }

    public function getPlantilla(): Plantilla
    {
        return $this->plantilla;
    }

    public function setPlantilla(Plantilla $plantilla): PlantillaJugador
    {
        $this->plantilla = $plantilla;
        return $this;
    }

}
