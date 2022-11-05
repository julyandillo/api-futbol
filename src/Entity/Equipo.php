<?php

namespace App\Entity;

use App\Repository\EquipoRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EquipoRepository::class)]
class Equipo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column(length: 255)]
    private ?string $nombreCompleto = null;

    #[ORM\Column(length: 5)]
    private ?string $nombreAbreviado = null;

    #[ORM\Column(length: 255)]
    private ?string $pais = null;

    #[ORM\Column]
    private ?int $fundacion = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $presidente = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ciudad = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $web = null;

    #[ORM\ManyToMany(targetEntity: Competicion::class, inversedBy: 'equipos')]
    #[ORM\JoinTable(name: 'equipos_competiciones')]
    private Collection $competiciones;

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

    public function getNombreCompleto(): ?string
    {
        return $this->nombreCompleto;
    }

    public function setNombreCompleto(string $nombreCompleto): self
    {
        $this->nombreCompleto = $nombreCompleto;

        return $this;
    }

    public function getNombreAbreviado(): ?string
    {
        return $this->nombreAbreviado;
    }

    public function setNombreAbreviado(string $nombreAbreviado): self
    {
        $this->nombreAbreviado = $nombreAbreviado;

        return $this;
    }

    public function getPais(): ?string
    {
        return $this->pais;
    }

    public function setPais(string $pais): self
    {
        $this->pais = $pais;

        return $this;
    }

    public function getFundacion(): ?int
    {
        return $this->fundacion;
    }

    public function setFundacion(int $fundacion): self
    {
        $this->fundacion = $fundacion;

        return $this;
    }

    public function getPresidente(): ?string
    {
        return $this->presidente;
    }

    public function setPresidente(string $presidente): self
    {
        $this->presidente = $presidente;

        return $this;
    }

    public function getCiudad(): ?string
    {
        return $this->ciudad;
    }

    public function setCiudad(?string $ciudad): self
    {
        $this->ciudad = $ciudad;

        return $this;
    }

    public function getWeb(): ?string
    {
        return $this->web;
    }

    public function setWeb(string $web): self
    {
        $this->web = $web;

        return $this;
    }

    public function getCompeticiones(): Collection
    {
        return $this->competiciones;
    }

    public function setCompeticiones(Collection $competiciones): void
    {
        $this->competiciones = $competiciones;
    }

    public function agregaCompeticionEnLaQueParticipa(Competicion $competicion): self
    {
        $this->competiciones->add($competicion);

        return $this;
    }
}
