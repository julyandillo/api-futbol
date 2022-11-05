<?php

namespace App\Entity;

use App\Config\CategoriaCompeticion;
use App\Config\TipoCompeticion;
use App\Repository\CompeticionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompeticionRepository::class)]
class Competicion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column(length: 255, enumType: TipoCompeticion::class)]
    private TipoCompeticion $tipo;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $fecha_inicio = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $fecha_fin = null;

    #[ORM\Column(length: 255, enumType: CategoriaCompeticion::class)]
    private CategoriaCompeticion $categoria;

    #[ORM\ManyToMany(targetEntity: Equipo::class, mappedBy: 'competiciones')]
    private Collection $equipos;

    public function __construct()
    {
        $this->equipos = new ArrayCollection();
    }

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

    public function getTipo(): TipoCompeticion
    {
        return $this->tipo;
    }

    public function setTipo(TipoCompeticion $tipo): self
    {
        $this->tipo = $tipo;

        return $this;
    }

    public function getFechaInicio(): ?\DateTimeInterface
    {
        return $this->fecha_inicio;
    }

    public function setFechaInicio(\DateTimeInterface $fecha_inicio): self
    {
        $this->fecha_inicio = $fecha_inicio;

        return $this;
    }

    public function getFechaFin(): ?\DateTimeInterface
    {
        return $this->fecha_fin;
    }

    public function setFechaFin(\DateTimeInterface $fecha_fin): self
    {
        $this->fecha_fin = $fecha_fin;

        return $this;
    }

    public function getCategoria(): CategoriaCompeticion
    {
        return $this->categoria;
    }

    public function setCategoria(CategoriaCompeticion $categoria): self
    {
        $this->categoria = $categoria;

        return $this;
    }

    public function getEquipos(): Collection
    {
        return $this->equipos;
    }

    public function setEquipos(Collection $equipos): Competicion
    {
        $this->equipos = $equipos;
        return $this;
    }


    public function agregarEquipo(Equipo $equipo): self
    {
        $this->equipos->add($equipo);

        return $this;
    }
}
