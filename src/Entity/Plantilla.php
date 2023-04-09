<?php

namespace App\Entity;

use App\Repository\PlantillaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlantillaRepository::class)]
class Plantilla
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(targetEntity: PlantillaJugador::class, mappedBy: 'plantilla', cascade: ['persist', 'remove'])]
    private Collection $plantillaJugadores;

    public function __construct()
    {
        $this->plantillaJugadores = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJugadores(): Collection
    {
        return $this->plantillaJugadores;
    }

    public function agregarJugador(Jugador $jugador, int $dorsal): self
    {
        $plantillaJugador = new PlantillaJugador();
        $plantillaJugador
            ->setJugador($jugador)
            ->setPlantilla($this)
            ->setDorsal($dorsal);

        $this->plantillaJugadores->add($plantillaJugador);

        return $this;
    }

}
