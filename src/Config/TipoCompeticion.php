<?php

namespace App\Config;

enum TipoCompeticion: string
{
    case Liga = 'LIGA';
    case TorneoConLiguilla = 'TORNEO_CON_LIGA';
    case TorneoSoloConEliminatorias = 'TORNEO_KO';

    public function nameForSelect(): string
    {
        return match ($this) {
            self::Liga => 'Liga',
            self::TorneoConLiguilla => 'Torneo con fase de grupos',
            self::TorneoSoloConEliminatorias => 'Sólo eliminatorias',
        };
    }
}
