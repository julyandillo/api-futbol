<?php

namespace App\Config;

enum TipoCompeticion: string
{
    case Liga = 'LIGA';
    case TorneoConGruposDobles = 'TORNEO_CON_GRUPOS_DOBLES';
    case TorneoConGruposSimples = 'TORNEO_CON_GRUPOS_SIMPLES';
    case TorneoSoloConEliminatorias = 'TORNEO_KO';

    public function nameForSelect(): string
    {
        return match ($this) {
            self::Liga => 'Liga',
            self::TorneoSoloConEliminatorias => 'Sólo eliminatorias',
            self::TorneoConGruposDobles => 'Torneo con fase de grupos de doble enfrentamiento',
            self::TorneoConGruposSimples => 'Torneo con fase de grupos de único enfrentamiento',
        };
    }
}
