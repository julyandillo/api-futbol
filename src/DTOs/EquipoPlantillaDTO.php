<?php

namespace App\DTOs;

use App\Entity\Equipo;
use App\Entity\Plantilla;

class EquipoPlantillaDTO
{
    private Equipo $equipo;

    private Plantilla $plantilla;

    /**
     * @param Equipo $equipo
     * @param Plantilla $plantilla
     */
    public function __construct(Equipo $equipo, Plantilla $plantilla)
    {
        $this->equipo = $equipo;
        $this->plantilla = $plantilla;
    }

    /**
     * @return Equipo
     */
    public function getEquipo(): Equipo
    {
        return $this->equipo;
    }

    /**
     * @param Equipo $equipo
     * @return EquipoPlantillaDTO
     */
    public function setEquipo(Equipo $equipo): EquipoPlantillaDTO
    {
        $this->equipo = $equipo;
        return $this;
    }

    /**
     * @return Plantilla
     */
    public function getPlantilla(): Plantilla
    {
        return $this->plantilla;
    }

    /**
     * @param Plantilla $plantilla
     * @return EquipoPlantillaDTO
     */
    public function setPlantilla(Plantilla $plantilla): EquipoPlantillaDTO
    {
        $this->plantilla = $plantilla;
        return $this;
    }


}