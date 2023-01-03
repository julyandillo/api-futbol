<?php

namespace App\Entity;

use App\Repository\EstadioRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EstadioRepository::class)]
class Estadio
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column(length: 255)]
    private ?string $ciudad = null;

    #[ORM\Column]
    private ?int $capacidad = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dimensiones = null;

    #[ORM\Column(nullable: true)]
    private ?int $construccion = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getCiudad(): ?string
    {
        return $this->ciudad;
    }

    public function setCiudad(string $ciudad): self
    {
        $this->ciudad = $ciudad;

        return $this;
    }

    public function getDimensiones(): ?string
    {
        return $this->dimensiones;
    }

    public function setDimensiones(?string $dimensiones): self
    {
        $this->dimensiones = $dimensiones;

        return $this;
    }

    public function getConstruccion(): ?int
    {
        return $this->construccion;
    }

    public function setConstruccion(?int $construccion): self
    {
        $this->construccion = $construccion;

        return $this;
    }

    public function getCapacidad(): ?int
    {
        return $this->capacidad;
    }

    public function setCapacidad(int $capacidad): self
    {
        $this->capacidad = $capacidad;

        return $this;
    }
}