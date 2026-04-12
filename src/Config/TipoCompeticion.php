<?php

namespace App\Config;

use Symfony\Component\Translation\TranslatableMessage;

enum TipoCompeticion: string
{
    case Liga = 'LIGA';
    case TorneoConGruposDobles = 'TORNEO_CON_GRUPOS_DOBLES';
    case TorneoConGruposSimples = 'TORNEO_CON_GRUPOS_SIMPLES';
    case TorneoSoloConEliminatorias = 'TORNEO_KO';
    case TorneoConLigaPrevia = 'TORNEO_CON_LIGA_PREVIA';

    public function nameForSelect(): TranslatableMessage
    {
        $message = match ($this) {
            self::Liga => 'League',
            self::TorneoSoloConEliminatorias => 'Only qualifying round',
            self::TorneoConGruposDobles => 'Tournament featuring a double-round-robin group stage',
            self::TorneoConGruposSimples => 'Tournament with a single-match group stage',
            self::TorneoConLigaPrevia => 'Tournament featuring a group stage prior to the knockout rounds',
        };

        return new TranslatableMessage($message, [], 'forms');
    }
}
