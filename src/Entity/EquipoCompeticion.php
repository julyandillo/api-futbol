<?php

namespace App\Entity;

use App\Repository\EquipoCompeticionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EquipoCompeticionRepository::class)]
class EquipoCompeticion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    #[ORM\ManyToOne(targetEntity: Equipo::class, inversedBy: 'equipoCompeticiones')]
    #[ORM\JoinColumn(nullable: false)]
    private Equipo $equipo;

    #[ORM\ManyToOne(targetEntity: Competicion::class, inversedBy: 'competicionEquipos')]
    #[ORM\JoinColumn(nullable: false)]
    private Competicion $competicion;

    #[ORM\ManyToOne(targetEntity: Plantilla::class, inversedBy: 'plantillaJugadores')]
    #[ORM\JoinColumn(nullable: false)]
    private Plantilla $plantilla;


    public function getEquipo(): Equipo
    {
        return $this->equipo;
    }

    public function setEquipo(Equipo $equipo): self
    {
        $this->equipo = $equipo;
        return $this;
    }

    public function getCompeticion(): Competicion
    {
        return $this->competicion;
    }

    public function setCompeticion(Competicion $competicion): self
    {
        $this->competicion = $competicion;
        return $this;
    }

    public function getPlantilla(): Plantilla
    {
        return $this->plantilla;
    }

    public function setPlantilla(Plantilla $plantilla): self
    {
        $this->plantilla = $plantilla;
        return $this;
    }
}
